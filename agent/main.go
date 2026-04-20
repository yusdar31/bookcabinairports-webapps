package main

import (
	"context"
	"encoding/json"
	"log"
	"net/http"
	"os"
	"os/signal"
	"syscall"
	"time"

	"github.com/aws/aws-sdk-go-v2/aws"
	"github.com/aws/aws-sdk-go-v2/config"
	"github.com/aws/aws-sdk-go-v2/service/sqs"
	"github.com/aws/aws-sdk-go-v2/service/sqs/types"
)

// OTAEvent represents an inbound OTA webhook message from SQS
type OTAEvent struct {
	EventType string          `json:"event_type"` // booking.created, booking.cancelled, booking.modified, payment.confirmed
	Provider  string          `json:"provider"`   // agoda, traveloka, booking_com
	Payload   json.RawMessage `json:"payload"`
}

func main() {
	log.SetFlags(log.LstdFlags | log.Lshortfile)
	log.Println("Bookcabin Booking Agent starting...")

	// Initialize MySQL
	InitDB()
	defer CloseDB()

	// Health check endpoint (untuk Docker/monitoring)
	go func() {
		http.HandleFunc("/health", func(w http.ResponseWriter, _ *http.Request) {
			if err := HealthCheckDB(); err != nil {
				w.WriteHeader(500)
				w.Write([]byte("db error"))
				return
			}
			w.Write([]byte("ok"))
		})
		log.Println("Health endpoint listening on :9000")
		http.ListenAndServe(":9000", nil)
	}()

	queueURL := mustEnv("SQS_QUEUE_URL")
	awsRegion := getEnv("AWS_DEFAULT_REGION", "ap-southeast-1")

	cfg, err := config.LoadDefaultConfig(context.TODO(), config.WithRegion(awsRegion))
	if err != nil {
		log.Fatalf("unable to load AWS config: %v", err)
	}

	client := sqs.NewFromConfig(cfg)

	ctx, cancel := context.WithCancel(context.Background())
	defer cancel()

	quit := make(chan os.Signal, 1)
	signal.Notify(quit, os.Interrupt, syscall.SIGTERM)
	go func() {
		<-quit
		log.Println("Shutdown signal received, stopping consumer...")
		cancel()
	}()

	log.Printf("Polling SQS queue: %s\n", queueURL)
	poll(ctx, client, queueURL)
	log.Println("Booking Agent stopped.")
}

func poll(ctx context.Context, client *sqs.Client, queueURL string) {
	for {
		select {
		case <-ctx.Done():
			return
		default:
		}

		out, err := client.ReceiveMessage(ctx, &sqs.ReceiveMessageInput{
			QueueUrl:            aws.String(queueURL),
			MaxNumberOfMessages: 10,
			WaitTimeSeconds:     20, // long polling
			VisibilityTimeout:   30,
		})
		if err != nil {
			if ctx.Err() != nil {
				return
			}
			log.Printf("ReceiveMessage error: %v — retrying in 5s\n", err)
			time.Sleep(5 * time.Second)
			continue
		}

		for _, msg := range out.Messages {
			processMessage(ctx, client, queueURL, msg)
		}
	}
}

func processMessage(ctx context.Context, client *sqs.Client, queueURL string, msg types.Message) {
	var event OTAEvent
	if err := json.Unmarshal([]byte(aws.ToString(msg.Body)), &event); err != nil {
		log.Printf("Invalid message format, skipping: %v\n", err)
		deleteMessage(ctx, client, queueURL, msg.ReceiptHandle)
		return
	}

	log.Printf("Processing event: type=%s provider=%s\n", event.EventType, event.Provider)

	var handlerErr error
	switch event.EventType {
	case "booking.created":
		handlerErr = handleBookingCreated(event.Payload)
	case "booking.cancelled":
		handlerErr = handleBookingCancelled(event.Payload)
	case "booking.modified":
		handlerErr = handleBookingModified(event.Payload)
	case "payment.confirmed":
		handlerErr = handlePaymentConfirmed(event.Payload)
	default:
		log.Printf("Unknown event type: %s — skipping\n", event.EventType)
	}

	if handlerErr != nil {
		log.Printf("Handler error for event %s: %v — message stays in queue\n", event.EventType, handlerErr)
		return
	}

	deleteMessage(ctx, client, queueURL, msg.ReceiptHandle)
}

func deleteMessage(ctx context.Context, client *sqs.Client, queueURL string, receiptHandle *string) {
	_, err := client.DeleteMessage(ctx, &sqs.DeleteMessageInput{
		QueueUrl:      aws.String(queueURL),
		ReceiptHandle: receiptHandle,
	})
	if err != nil {
		log.Printf("Failed to delete message: %v\n", err)
	}
}

// --- Helpers ---

func mustEnv(key string) string {
	v := os.Getenv(key)
	if v == "" {
		log.Fatalf("required environment variable %s is not set", key)
	}
	return v
}

func getEnv(key, fallback string) string {
	if v := os.Getenv(key); v != "" {
		return v
	}
	return fallback
}

