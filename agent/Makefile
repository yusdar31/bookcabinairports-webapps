.PHONY: build run test clean

BINARY=booking-agent

build:
	go mod tidy
	go build -o $(BINARY) .

run: build
	./$(BINARY)

test:
	go test ./... -v

clean:
	rm -f $(BINARY)

# Cross-compile for Linux (EC2 Ubuntu)
build-linux:
	GOOS=linux GOARCH=amd64 go build -o $(BINARY)-linux .
