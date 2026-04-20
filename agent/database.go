package main

import (
	"database/sql"
	"fmt"
	"log"
	"os"
	"time"

	_ "github.com/go-sql-driver/mysql"
)

var db *sql.DB

// InitDB initializes MySQL connection pool
func InitDB() {
	host := getEnv("DB_HOST", "localhost")
	port := getEnv("DB_PORT", "3306")
	user := getEnv("DB_USERNAME", "bookcabin")
	pass := mustEnv("DB_PASSWORD")
	name := getEnv("DB_DATABASE", "bookcabin")

	dsn := fmt.Sprintf("%s:%s@tcp(%s:%s)/%s?parseTime=true&timeout=10s&readTimeout=30s&writeTimeout=30s",
		user, pass, host, port, name)

	var err error
	db, err = sql.Open("mysql", dsn)
	if err != nil {
		log.Fatalf("Failed to open DB connection: %v", err)
	}

	db.SetMaxOpenConns(5)
	db.SetMaxIdleConns(2)
	db.SetConnMaxLifetime(5 * time.Minute)

	if err = db.Ping(); err != nil {
		log.Fatalf("Failed to ping DB: %v", err)
	}

	log.Println("MySQL connection established")
}

// GetDB returns the database connection pool
func GetDB() *sql.DB {
	return db
}

// CloseDB closes the database connection
func CloseDB() {
	if db != nil {
		db.Close()
		log.Println("MySQL connection closed")
	}
}

// HealthCheckDB pings the database
func HealthCheckDB() error {
	if db == nil {
		return fmt.Errorf("database not initialized")
	}
	return db.Ping()
}

// getEnvDB is a helper (reusing from main but scoped here for clarity)
func getEnvDB(key, fallback string) string {
	if v := os.Getenv(key); v != "" {
		return v
	}
	return fallback
}
