variable "config" {
  description = "Environment configuration object"
  type        = any
}

variable "secret_overrides" {
  description = "Local-only sensitive overrides merged into config"
  type        = any
  default     = {}
}
