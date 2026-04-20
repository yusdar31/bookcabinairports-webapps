package main

import (
	"database/sql"
	"encoding/json"
	"fmt"
	"log"
	"math/rand"
	"time"
)

// BookingPayload represents OTA booking data
type BookingPayload struct {
	OTABookingID string  `json:"ota_booking_id"`
	GuestName    string  `json:"guest_name"`
	GuestEmail   string  `json:"guest_email"`
	GuestPhone   string  `json:"guest_phone"`
	RoomType     string  `json:"room_type"` // standard, vip
	CheckIn      string  `json:"check_in"`  // 2026-04-20T14:00:00
	CheckOut     string  `json:"check_out"`
	TotalPrice   float64 `json:"total_price"`
}

// PaymentPayload represents OTA payment confirmation
type PaymentPayload struct {
	OTABookingID     string  `json:"ota_booking_id"`
	PaymentReference string  `json:"payment_reference"`
	Amount           float64 `json:"amount"`
}

func handleBookingCreated(payload json.RawMessage) error {
	var p BookingPayload
	if err := json.Unmarshal(payload, &p); err != nil {
		return fmt.Errorf("invalid booking payload: %w", err)
	}

	log.Printf("booking.created: OTA=%s guest=%s room_type=%s", p.OTABookingID, p.GuestName, p.RoomType)

	tx, err := db.Begin()
	if err != nil {
		return fmt.Errorf("begin tx: %w", err)
	}
	defer tx.Rollback()

	// Find available room (SELECT FOR UPDATE)
	var roomID int
	var roomNumber string
	err = tx.QueryRow(`
		SELECT id, room_number FROM rooms 
		WHERE type = ? AND status = 'available' AND is_active = 1
		ORDER BY room_number ASC
		LIMIT 1
		FOR UPDATE
	`, p.RoomType).Scan(&roomID, &roomNumber)

	if err == sql.ErrNoRows {
		log.Printf("No available %s room for OTA booking %s", p.RoomType, p.OTABookingID)
		return nil // Don't retry — no rooms available
	}
	if err != nil {
		return fmt.Errorf("query room: %w", err)
	}

	// Check double-booking
	var conflict int
	err = tx.QueryRow(`
		SELECT COUNT(*) FROM bookings 
		WHERE room_id = ? 
		AND status NOT IN ('cancelled', 'no_show', 'checked_out')
		AND (
			(check_in BETWEEN ? AND ?) OR 
			(check_out BETWEEN ? AND ?) OR
			(check_in <= ? AND check_out >= ?)
		)
	`, roomID, p.CheckIn, p.CheckOut, p.CheckIn, p.CheckOut, p.CheckIn, p.CheckOut).Scan(&conflict)

	if err != nil {
		return fmt.Errorf("check conflict: %w", err)
	}

	if conflict > 0 {
		log.Printf("Room %s has conflict for OTA booking %s", roomNumber, p.OTABookingID)
		return nil // Don't retry
	}

	// Generate booking code and PIN
	bookingCode := fmt.Sprintf("BK-%s-%04d", time.Now().Format("20060102"), rand.Intn(10000))
	pinCode := fmt.Sprintf("%06d", rand.Intn(1000000))
	qrToken := fmt.Sprintf("%d-%s", time.Now().UnixNano(), p.OTABookingID)

	// Insert booking
	_, err = tx.Exec(`
		INSERT INTO bookings 
		(booking_code, room_id, guest_name, guest_email, guest_phone, 
		 check_in, check_out, total_price, status, pin_code, qr_token,
		 payment_method, source, ota_booking_id, created_at, updated_at)
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', ?, ?, 'ota', 'ota', ?, NOW(), NOW())
	`, bookingCode, roomID, p.GuestName, p.GuestEmail, p.GuestPhone,
		p.CheckIn, p.CheckOut, p.TotalPrice, pinCode, qrToken, p.OTABookingID)

	if err != nil {
		return fmt.Errorf("insert booking: %w", err)
	}

	// Update room status
	_, err = tx.Exec(`UPDATE rooms SET status = 'occupied', updated_at = NOW() WHERE id = ?`, roomID)
	if err != nil {
		return fmt.Errorf("update room: %w", err)
	}

	if err = tx.Commit(); err != nil {
		return fmt.Errorf("commit: %w", err)
	}

	log.Printf("OTA booking created: %s → room %s", bookingCode, roomNumber)
	return nil
}

func handleBookingCancelled(payload json.RawMessage) error {
	var p struct {
		OTABookingID string `json:"ota_booking_id"`
		Reason       string `json:"reason"`
	}
	if err := json.Unmarshal(payload, &p); err != nil {
		return fmt.Errorf("invalid cancel payload: %w", err)
	}

	log.Printf("booking.cancelled: OTA=%s reason=%s", p.OTABookingID, p.Reason)

	tx, err := db.Begin()
	if err != nil {
		return fmt.Errorf("begin tx: %w", err)
	}
	defer tx.Rollback()

	// Find booking
	var bookingID, roomID int
	err = tx.QueryRow(`
		SELECT id, room_id FROM bookings 
		WHERE ota_booking_id = ? AND status NOT IN ('cancelled', 'checked_out')
		FOR UPDATE
	`, p.OTABookingID).Scan(&bookingID, &roomID)

	if err == sql.ErrNoRows {
		log.Printf("OTA booking %s not found or already cancelled", p.OTABookingID)
		return nil
	}
	if err != nil {
		return fmt.Errorf("query booking: %w", err)
	}

	// Cancel booking
	_, err = tx.Exec(`UPDATE bookings SET status = 'cancelled', updated_at = NOW() WHERE id = ?`, bookingID)
	if err != nil {
		return fmt.Errorf("cancel booking: %w", err)
	}

	// Free room
	_, err = tx.Exec(`UPDATE rooms SET status = 'available', updated_at = NOW() WHERE id = ?`, roomID)
	if err != nil {
		return fmt.Errorf("free room: %w", err)
	}

	if err = tx.Commit(); err != nil {
		return fmt.Errorf("commit: %w", err)
	}

	log.Printf("OTA booking %s cancelled, room freed", p.OTABookingID)
	return nil
}

func handleBookingModified(payload json.RawMessage) error {
	var p BookingPayload
	if err := json.Unmarshal(payload, &p); err != nil {
		return fmt.Errorf("invalid modify payload: %w", err)
	}

	log.Printf("booking.modified: OTA=%s new_dates=%s to %s", p.OTABookingID, p.CheckIn, p.CheckOut)

	// Update dates & price
	result, err := db.Exec(`
		UPDATE bookings 
		SET check_in = ?, check_out = ?, total_price = ?, updated_at = NOW()
		WHERE ota_booking_id = ? AND status NOT IN ('cancelled', 'checked_out')
	`, p.CheckIn, p.CheckOut, p.TotalPrice, p.OTABookingID)

	if err != nil {
		return fmt.Errorf("update booking: %w", err)
	}

	rows, _ := result.RowsAffected()
	if rows == 0 {
		log.Printf("OTA booking %s not found for modification", p.OTABookingID)
	} else {
		log.Printf("OTA booking %s modified successfully", p.OTABookingID)
	}
	return nil
}

func handlePaymentConfirmed(payload json.RawMessage) error {
	var p PaymentPayload
	if err := json.Unmarshal(payload, &p); err != nil {
		return fmt.Errorf("invalid payment payload: %w", err)
	}

	log.Printf("payment.confirmed: OTA=%s ref=%s amount=%.0f", p.OTABookingID, p.PaymentReference, p.Amount)

	result, err := db.Exec(`
		UPDATE bookings 
		SET status = 'confirmed', payment_reference = ?, updated_at = NOW()
		WHERE ota_booking_id = ? AND status = 'pending'
	`, p.PaymentReference, p.OTABookingID)

	if err != nil {
		return fmt.Errorf("update payment: %w", err)
	}

	rows, _ := result.RowsAffected()
	if rows == 0 {
		log.Printf("OTA booking %s not found or not pending", p.OTABookingID)
	} else {
		log.Printf("OTA booking %s payment confirmed", p.OTABookingID)
	}
	return nil
}
