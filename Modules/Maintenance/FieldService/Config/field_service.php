<?php
return [
    'otp_expires_minutes'     => (int) env('FIELD_OTP_EXPIRES_MINUTES', 10),
    'position_retention_days' => (int) env('FIELD_POSITION_RETENTION_DAYS', 30),
    'redis_position_ttl'      => 3600,
    'verbale_storage_disk'    => 'minio',
    'verbale_storage_path'    => 'documents/verbali',
    'photos_storage_disk'     => 'minio',
    'photos_storage_path'     => 'documents/photos',
    'signatures_storage_disk' => 'minio',
    'signatures_storage_path' => 'documents/signatures',
];
