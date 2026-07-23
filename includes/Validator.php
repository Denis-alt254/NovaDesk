<?php
/**
 * Centralized Request Input Sanitizer and Validator
 */
class Validator {

    private array $data = [];
    private array $errors = [];
    private array $sanitized = [];

    public function __construct(?array $postData = null) {
        // Default to superglobal $_POST if no array passed
        $this->data = $postData ?? $_POST;
        $this->sanitizeAll();
    }

    /**
     * Automatically trims strings and normalizes input data.
     */
    private function sanitizeAll(): void {
        foreach ($this->data as $key => $value) {
            if (is_string($value)) {
                // Strip invisible control characters (except standard whitespace/newlines)
                $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', trim($value));
                $this->sanitized[$key] = $clean;
            } elseif (is_array($value)) {
                $this->sanitized[$key] = array_map(function ($item) {
                    return is_string($item) ? trim($item) : $item;
                }, $value);
            } else {
                $this->sanitized[$key] = $value;
            }
        }
    }

    /**
     * Validate required fields.
     */
    public function required(array $fields): self {
        foreach ($fields as $field => $label) {
            // Handle indexed array of field names vs associative array with custom labels
            $fieldName = is_int($field) ? $label : $field;
            $fieldLabel = is_int($field) ? ucfirst(str_replace('_', ' ', $fieldName)) : $label;

            $val = $this->sanitized[$fieldName] ?? null;
            if ($val === null || $val === '' || (is_array($val) && empty($val))) {
                $this->addError($fieldName, "{$fieldLabel} is required.");
            }
        }
        return $this;
    }

    /**
     * Validate email format.
     */
    public function email(string $field, string $label = 'Email address'): self {
        $val = $this->sanitized[$field] ?? '';
        if ($val !== '' && !filter_var($val, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, "Please provide a valid {$label}.");
        }
        return $this;
    }

    /**
     * Validate minimum string length.
     */
    public function minLength(string $field, int $min, ?string $label = null): self {
        $val = $this->sanitized[$field] ?? '';
        $fieldLabel = $label ?? ucfirst(str_replace('_', ' ', $field));

        if ($val !== '' && mb_strlen($val, 'UTF-8') < $min) {
            $this->addError($field, "{$fieldLabel} must be at least {$min} characters long.");
        }
        return $this;
    }

    /**
     * Validate maximum string length (prevents DB truncation attacks).
     */
    public function maxLength(string $field, int $max, ?string $label = null): self {
        $val = $this->sanitized[$field] ?? '';
        $fieldLabel = $label ?? ucfirst(str_replace('_', ' ', $field));

        if ($val !== '' && mb_strlen($val, 'UTF-8') > $max) {
            $this->addError($field, "{$fieldLabel} cannot exceed {$max} characters.");
        }
        return $this;
    }

    /**
     * Validate value exists within a fixed whitelist (for selects, radio buttons, statuses).
     */
    public function inList(string $field, array $allowed, ?string $label = null): self {
        $val = $this->sanitized[$field] ?? '';
        $fieldLabel = $label ?? ucfirst(str_replace('_', ' ', $field));

        if ($val !== '' && !in_array($val, $allowed, true)) {
            $this->addError($field, "Invalid option selected for {$fieldLabel}.");
        }
        return $this;
    }

    /**
     * Add custom validation error manually.
     */
    public function addError(string $field, string $message): void {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
    }

    /**
     * Returns true if no validation errors occurred.
     */
    public function isValid(): bool {
        return empty($this->errors);
    }

    /**
     * Get all validation error messages.
     */
    public function getErrors(): array {
        return $this->errors;
    }

    /**
     * Get first error message (ideal for quick top-of-form alerts).
     */
    public function getFirstError(): ?string {
        return reset($this->errors) ?: null;
    }

    /**
     * Retrieve all sanitized POST data or a specific field value.
     */
    public function get(?string $field = null, mixed $default = null): mixed {
        if ($field === null) {
            return $this->sanitized;
        }
        return $this->sanitized[$field] ?? $default;
    }
}