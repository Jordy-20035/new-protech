-- ProTech Database Schema for PostgreSQL
-- Run this SQL to set up the database

-- Create database (run this separately first)
-- CREATE DATABASE protech_db;

-- Connect to protech_db before running the rest

-- Users table (clients and workers base info)
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    user_type VARCHAR(10) NOT NULL DEFAULT 'user' CHECK (user_type IN ('user', 'worker')),
    avatar VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Workers table (additional info for professionals)
CREATE TABLE IF NOT EXISTS workers (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL UNIQUE REFERENCES users(id) ON DELETE CASCADE,
    service_area VARCHAR(255) NOT NULL,
    skills VARCHAR(500) NOT NULL,
    experience INTEGER NOT NULL DEFAULT 0,
    hourly_rate DECIMAL(10,2) DEFAULT 50.00,
    rating DECIMAL(3,2) DEFAULT 0.00,
    total_reviews INTEGER DEFAULT 0,
    total_jobs INTEGER DEFAULT 0,
    is_available BOOLEAN DEFAULT TRUE,
    bio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Service categories
CREATE TABLE IF NOT EXISTS service_categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default service categories
INSERT INTO service_categories (name, description, icon) VALUES
('Plumbing', 'Pipe repair, installation, drain cleaning', 'wrench'),
('Electrical', 'Wiring, outlets, lighting installation', 'zap'),
('Cleaning', 'House cleaning, deep cleaning, move-out cleaning', 'sparkles'),
('Carpentry', 'Furniture repair, woodwork, installations', 'hammer'),
('HVAC', 'Heating, ventilation, air conditioning', 'thermometer'),
('Painting', 'Interior and exterior painting', 'paintbrush'),
('Landscaping', 'Lawn care, gardening, tree trimming', 'tree'),
('Appliance Repair', 'Fixing household appliances', 'settings')
ON CONFLICT DO NOTHING;

-- Bookings table
CREATE TABLE IF NOT EXISTS bookings (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    worker_id INTEGER NOT NULL REFERENCES workers(id) ON DELETE CASCADE,
    service_category_id INTEGER REFERENCES service_categories(id) ON DELETE SET NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    scheduled_date DATE NOT NULL,
    scheduled_time TIME NOT NULL,
    estimated_duration INTEGER DEFAULT 60,
    address VARCHAR(500) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'confirmed', 'in_progress', 'completed', 'cancelled')),
    payment_status VARCHAR(20) DEFAULT 'pending' CHECK (payment_status IN ('pending', 'paid', 'refunded')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Reviews table
CREATE TABLE IF NOT EXISTS reviews (
    id SERIAL PRIMARY KEY,
    booking_id INTEGER NOT NULL UNIQUE REFERENCES bookings(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    worker_id INTEGER NOT NULL REFERENCES workers(id) ON DELETE CASCADE,
    rating INTEGER NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Messages table for chat between users and workers
CREATE TABLE IF NOT EXISTS messages (
    id SERIAL PRIMARY KEY,
    sender_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    receiver_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    booking_id INTEGER REFERENCES bookings(id) ON DELETE SET NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(20) DEFAULT 'system' CHECK (type IN ('booking', 'review', 'message', 'system')),
    is_read BOOLEAN DEFAULT FALSE,
    link VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Favorites table (users can save favorite workers)
CREATE TABLE IF NOT EXISTS favorites (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    worker_id INTEGER NOT NULL REFERENCES workers(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (user_id, worker_id)
);

-- Worker availability schedule
CREATE TABLE IF NOT EXISTS worker_availability (
    id SERIAL PRIMARY KEY,
    worker_id INTEGER NOT NULL REFERENCES workers(id) ON DELETE CASCADE,
    day_of_week SMALLINT NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_available BOOLEAN DEFAULT TRUE
);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_bookings_user ON bookings(user_id);
CREATE INDEX IF NOT EXISTS idx_bookings_worker ON bookings(worker_id);
CREATE INDEX IF NOT EXISTS idx_bookings_status ON bookings(status);
CREATE INDEX IF NOT EXISTS idx_bookings_date ON bookings(scheduled_date);
CREATE INDEX IF NOT EXISTS idx_reviews_worker ON reviews(worker_id);
CREATE INDEX IF NOT EXISTS idx_messages_receiver ON messages(receiver_id, is_read);
CREATE INDEX IF NOT EXISTS idx_notifications_user ON notifications(user_id, is_read);

-- Function to update worker rating
CREATE OR REPLACE FUNCTION update_worker_rating()
RETURNS TRIGGER AS $$
BEGIN
    UPDATE workers 
    SET rating = (
        SELECT COALESCE(AVG(rating), 0) FROM reviews WHERE worker_id = NEW.worker_id
    ),
    total_reviews = (
        SELECT COUNT(*) FROM reviews WHERE worker_id = NEW.worker_id
    )
    WHERE id = NEW.worker_id;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Trigger to update worker rating after a new review
DROP TRIGGER IF EXISTS trigger_update_worker_rating ON reviews;
CREATE TRIGGER trigger_update_worker_rating
AFTER INSERT ON reviews
FOR EACH ROW
EXECUTE FUNCTION update_worker_rating();

-- Function to update worker total jobs
CREATE OR REPLACE FUNCTION update_worker_jobs()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.status = 'completed' AND OLD.status != 'completed' THEN
        UPDATE workers 
        SET total_jobs = total_jobs + 1
        WHERE id = NEW.worker_id;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Trigger to update worker total jobs after booking completion
DROP TRIGGER IF EXISTS trigger_update_worker_jobs ON bookings;
CREATE TRIGGER trigger_update_worker_jobs
AFTER UPDATE ON bookings
FOR EACH ROW
EXECUTE FUNCTION update_worker_jobs();

-- =====================================================
-- SAMPLE DATA (Optional - for testing)
-- =====================================================

-- Sample Users (password is 'password123' hashed with bcrypt)
INSERT INTO users (name, email, phone, password, user_type) VALUES
('John Client', 'john@example.com', '+1234567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
('Mike Plumber', 'mike@example.com', '+1234567891', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'worker'),
('Sarah Electrician', 'sarah@example.com', '+1234567892', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'worker'),
('David Cleaner', 'david@example.com', '+1234567893', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'worker')
ON CONFLICT (email) DO NOTHING;

-- Sample Workers
INSERT INTO workers (user_id, service_area, skills, experience, hourly_rate, rating, total_reviews, total_jobs) 
SELECT id, 'New York', 'Plumbing, Pipe Repair, Drain Cleaning', 8, 75.00, 4.8, 42, 156
FROM users WHERE email = 'mike@example.com'
ON CONFLICT (user_id) DO NOTHING;

INSERT INTO workers (user_id, service_area, skills, experience, hourly_rate, rating, total_reviews, total_jobs) 
SELECT id, 'New York', 'Electrical, Wiring, Lighting', 6, 85.00, 4.9, 38, 120
FROM users WHERE email = 'sarah@example.com'
ON CONFLICT (user_id) DO NOTHING;

INSERT INTO workers (user_id, service_area, skills, experience, hourly_rate, rating, total_reviews, total_jobs) 
SELECT id, 'Los Angeles', 'House Cleaning, Deep Cleaning', 4, 45.00, 4.7, 65, 230
FROM users WHERE email = 'david@example.com'
ON CONFLICT (user_id) DO NOTHING;
