-- Pet Management System Database

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS pet_management;
USE pet_management;

-- Pet Categories Table
CREATE TABLE IF NOT EXISTS pet_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Pets Table
CREATE TABLE IF NOT EXISTS pets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category_id INT,
    breed VARCHAR(100),
    age INT,
    gender ENUM('Male', 'Female'),
    size ENUM('Small', 'Medium', 'Large'),
    color VARCHAR(50),
    description TEXT,
    medical_history TEXT,
    is_available BOOLEAN DEFAULT 1,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES pet_categories(id)
);

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Adoption Requests Table
CREATE TABLE IF NOT EXISTS adoption_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pet_id INT,
    user_id INT,
    reason TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pet_id) REFERENCES pets(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Veterinary Clinics Table
CREATE TABLE IF NOT EXISTS vet_clinics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(100),
    description TEXT,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Clinic Hours Table
CREATE TABLE IF NOT EXISTS clinic_hours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clinic_id INT,
    day VARCHAR(20),
    open_time TIME,
    close_time TIME,
    FOREIGN KEY (clinic_id) REFERENCES vet_clinics(id)
);

-- Clinic Services Table
CREATE TABLE IF NOT EXISTS clinic_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clinic_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2),
    FOREIGN KEY (clinic_id) REFERENCES vet_clinics(id)
);

-- Vet Appointments Table
CREATE TABLE IF NOT EXISTS vet_appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pet_id INT,
    clinic_id INT,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    reason TEXT,
    status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pet_id) REFERENCES pets(id),
    FOREIGN KEY (clinic_id) REFERENCES vet_clinics(id)
);

-- Appointment Services Junction Table
CREATE TABLE IF NOT EXISTS vet_appointment_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT,
    service_id INT,
    FOREIGN KEY (appointment_id) REFERENCES vet_appointments(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES clinic_services(id)
);

-- Pricing Plans Table
CREATE TABLE IF NOT EXISTS pricing_plans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description TEXT,
    features TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Pricing Plan Orders Table
CREATE TABLE IF NOT EXISTS pricing_plan_orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    plan_id INT NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'active', 'cancelled') DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (plan_id) REFERENCES pricing_plans(id)
);

-- Insert/Update Pet Categories with proper images
INSERT INTO pet_categories (name, description, image_url) 
VALUES ('Dogs', 'Friendly canine companions for adoption', 'img/Catagory Image/Dogs_Category.png')
ON DUPLICATE KEY UPDATE 
    description = VALUES(description), 
    image_url = VALUES(image_url);

INSERT INTO pet_categories (name, description, image_url) 
VALUES ('Cats', 'Adorable feline friends looking for forever homes', 'img/Catagory Image/Cats1_Category.png')
ON DUPLICATE KEY UPDATE 
    description = VALUES(description), 
    image_url = VALUES(image_url);

INSERT INTO pet_categories (name, description, image_url) 
VALUES ('Birds', 'Beautiful avian companions for adoption', 'img/Catagory Image/Birds_Category.png')
ON DUPLICATE KEY UPDATE 
    description = VALUES(description), 
    image_url = VALUES(image_url);

INSERT INTO pet_categories (name, description, image_url) 
VALUES ('Others', 'Other unique pets seeking loving homes', 'img/Catagory Image/Others_Category.jpg')
ON DUPLICATE KEY UPDATE 
    description = VALUES(description), 
    image_url = VALUES(image_url);

-- Insert pet data
INSERT INTO pets (name, category_id, breed, age, gender, size, color, description, medical_history, is_available, image_url)
VALUES ('Max', 1, 'Golden Retriever', 2, 'Male', 'Large', 'Golden', 'Friendly and playful dog, good with children', 'Vaccinated and neutered', 1, 'img/dogs/Dog 1.png')
ON DUPLICATE KEY UPDATE 
    category_id = VALUES(category_id),
    breed = VALUES(breed),
    age = VALUES(age),
    gender = VALUES(gender),
    size = VALUES(size),
    color = VALUES(color),
    description = VALUES(description),
    medical_history = VALUES(medical_history),
    is_available = VALUES(is_available),
    image_url = VALUES(image_url);

INSERT INTO pets (name, category_id, breed, age, gender, size, color, description, medical_history, is_available, image_url)
VALUES ('Buddy', 1, 'German Shepherd', 3, 'Male', 'Large', 'Black/Tan', 'Intelligent and loyal companion', 'Vaccinated and healthy', 1, 'img/dogs/Dog 3.png')
ON DUPLICATE KEY UPDATE 
    category_id = VALUES(category_id),
    breed = VALUES(breed),
    age = VALUES(age),
    gender = VALUES(gender),
    size = VALUES(size),
    color = VALUES(color),
    description = VALUES(description),
    medical_history = VALUES(medical_history),
    is_available = VALUES(is_available),
    image_url = VALUES(image_url);

INSERT INTO pets (name, category_id, breed, age, gender, size, color, description, medical_history, is_available, image_url)
VALUES ('Luna', 2, 'Siamese', 2, 'Female', 'Medium', 'Cream/Brown', 'Elegant and vocal cat who loves attention', 'Vaccinated and spayed', 1, 'img/cats/Cat 1.jpg')
ON DUPLICATE KEY UPDATE 
    category_id = VALUES(category_id),
    breed = VALUES(breed),
    age = VALUES(age),
    gender = VALUES(gender),
    size = VALUES(size),
    color = VALUES(color),
    description = VALUES(description),
    medical_history = VALUES(medical_history),
    is_available = VALUES(is_available),
    image_url = VALUES(image_url);

INSERT INTO pets (name, category_id, breed, age, gender, size, color, description, medical_history, is_available, image_url)
VALUES ('Oliver', 2, 'Maine Coon', 1, 'Male', 'Large', 'Gray/White', 'Gentle giant with a friendly personality', 'Vaccinated and neutered', 1, 'img/cats/Cat 7.png')
ON DUPLICATE KEY UPDATE 
    category_id = VALUES(category_id),
    breed = VALUES(breed),
    age = VALUES(age),
    gender = VALUES(gender),
    size = VALUES(size),
    color = VALUES(color),
    description = VALUES(description),
    medical_history = VALUES(medical_history),
    is_available = VALUES(is_available),
    image_url = VALUES(image_url);

INSERT INTO pets (name, category_id, breed, age, gender, size, color, description, medical_history, is_available, image_url)
VALUES ('Sunny', 3, 'Canary', 1, 'Male', 'Small', 'Yellow', 'Bright yellow canary with a beautiful singing voice', 'Healthy and active', 1, 'img/birds/Bird 1.png')
ON DUPLICATE KEY UPDATE 
    category_id = VALUES(category_id),
    breed = VALUES(breed),
    age = VALUES(age),
    gender = VALUES(gender),
    size = VALUES(size),
    color = VALUES(color),
    description = VALUES(description),
    medical_history = VALUES(medical_history),
    is_available = VALUES(is_available),
    image_url = VALUES(image_url);

INSERT INTO pets (name, category_id, breed, age, gender, size, color, description, medical_history, is_available, image_url)
VALUES ('Blue', 3, 'Budgerigar', 2, 'Male', 'Small', 'Blue', 'Colorful and playful budgie who loves to interact', 'Regular check-ups, all healthy', 1, 'img/birds/Bird 2.png')
ON DUPLICATE KEY UPDATE 
    category_id = VALUES(category_id),
    breed = VALUES(breed),
    age = VALUES(age),
    gender = VALUES(gender),
    size = VALUES(size),
    color = VALUES(color),
    description = VALUES(description),
    medical_history = VALUES(medical_history),
    is_available = VALUES(is_available),
    image_url = VALUES(image_url);

-- Insert pet data for other pets
INSERT INTO pets (name, category_id, breed, age, gender, size, color, description, medical_history, is_available, image_url)
VALUES ('Thumper', 4, 'Holland Lop Rabbit', 1, 'Male', 'Small', 'White/Brown', 'Friendly rabbit with floppy ears, great for families', 'Regular check-ups, vaccinated', 1, 'img/others/Rabit 1.jpg')
ON DUPLICATE KEY UPDATE 
    category_id = VALUES(category_id),
    breed = VALUES(breed),
    age = VALUES(age),
    gender = VALUES(gender),
    size = VALUES(size),
    color = VALUES(color),
    description = VALUES(description),
    medical_history = VALUES(medical_history),
    is_available = VALUES(is_available),
    image_url = VALUES(image_url);

INSERT INTO pets (name, category_id, breed, age, gender, size, color, description, medical_history, is_available, image_url)
VALUES ('Shelly', 4, 'Red-Eared Slider Turtle', 3, 'Female', 'Small', 'Green/Red', 'Calm and easy to care for turtle, perfect for beginners', 'Healthy shell and good activity level', 1, 'img/others/tortos1.png')
ON DUPLICATE KEY UPDATE 
    category_id = VALUES(category_id),
    breed = VALUES(breed),
    age = VALUES(age),
    gender = VALUES(gender),
    size = VALUES(size),
    color = VALUES(color),
    description = VALUES(description),
    medical_history = VALUES(medical_history),
    is_available = VALUES(is_available),
    image_url = VALUES(image_url);

-- Create a basic admin user if not exists
INSERT INTO users (name, email, password, role) 
VALUES ('Administrator', 'admin@gmail.com', '$2y$10$7rLSvRVyTQORapkDOqmkhetjF6H9lJHngr4hJMSM2lHXn0Q/nEzaa', 'admin')
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    role = VALUES(role);

-- Insert clinic data with images
INSERT INTO vet_clinics (name, address, phone, email, description, image)
VALUES ('PetCare Clinic', 'Kurmitola, Airport, Dhaka', '+8801700000001', 'petcare@example.com', 'Full-service veterinary clinic offering preventive care, surgery, and emergency services.', 'img/Clinic_image/Clinic 1.jpg')
ON DUPLICATE KEY UPDATE 
    address = VALUES(address),
    phone = VALUES(phone),
    email = VALUES(email),
    description = VALUES(description),
    image = VALUES(image);

INSERT INTO vet_clinics (name, address, phone, email, description, image)
VALUES ('Animal Health Center', 'Banani, Dhaka', '+8801700000002', 'ahc@example.com', 'Specialized clinic with advanced diagnostic equipment and experienced veterinarians.', 'img/Clinic_image/Clinic 2.jpg')
ON DUPLICATE KEY UPDATE 
    address = VALUES(address),
    phone = VALUES(phone),
    email = VALUES(email),
    description = VALUES(description),
    image = VALUES(image);

INSERT INTO vet_clinics (name, address, phone, email, description, image)
VALUES ('Pet Wellness Center', 'Gulshan, Dhaka', '+8801700000003', 'wellness@example.com', 'Focused on holistic pet care with services including acupuncture and nutritional counseling.', 'img/Clinic_image/Clinic 3.jpg')
ON DUPLICATE KEY UPDATE 
    address = VALUES(address),
    phone = VALUES(phone),
    email = VALUES(email),
    description = VALUES(description),
    image = VALUES(image);

INSERT INTO vet_clinics (name, address, phone, email, description, image)
VALUES ('Emergency Pet Hospital', 'Mirpur, Dhaka', '+8801700000004', 'emergency@example.com', '24/7 emergency care facility with state-of-the-art equipment and critical care specialists.', 'img/Clinic_image/Clinic 4.jpg')
ON DUPLICATE KEY UPDATE 
    address = VALUES(address),
    phone = VALUES(phone),
    email = VALUES(email),
    description = VALUES(description),
    image = VALUES(image);

-- Insert clinic hours
INSERT INTO clinic_hours (clinic_id, day, open_time, close_time)
VALUES 
(1, 'Monday', '09:00:00', '18:00:00'),
(1, 'Tuesday', '09:00:00', '18:00:00'),
(1, 'Wednesday', '09:00:00', '18:00:00'),
(1, 'Thursday', '09:00:00', '18:00:00'),
(1, 'Friday', '09:00:00', '18:00:00'),
(1, 'Saturday', '10:00:00', '16:00:00'),
(1, 'Sunday', 'Closed', 'Closed'),
(2, 'Monday', '08:00:00', '20:00:00'),
(2, 'Tuesday', '08:00:00', '20:00:00'),
(2, 'Wednesday', '08:00:00', '20:00:00'),
(2, 'Thursday', '08:00:00', '20:00:00'),
(2, 'Friday', '08:00:00', '18:00:00'),
(2, 'Saturday', '10:00:00', '16:00:00'),
(2, 'Sunday', 'Closed', 'Closed')
ON DUPLICATE KEY UPDATE
    open_time = VALUES(open_time),
    close_time = VALUES(close_time);

-- Insert clinic services
INSERT INTO clinic_services (clinic_id, name, description, price)
VALUES 
(1, 'Vaccination', 'Complete vaccination package for pets including rabies and distemper', 50.00),
(1, 'Wellness Exam', 'Comprehensive physical examination and health assessment', 35.00),
(1, 'Dental Cleaning', 'Complete dental cleaning including scaling and polishing', 120.00),
(1, 'Spay/Neuter', 'Surgical sterilization procedure', 200.00),
(2, 'Vaccination', 'Standard vaccination package for pets', 45.00),
(2, 'X-Ray', 'Digital radiography for diagnostic imaging', 80.00),
(2, 'Surgery - Minor', 'Minor surgical procedures such as mass removal', 150.00),
(2, 'Surgery - Major', 'Major surgical procedures such as fracture repair', 350.00),
(3, 'Acupuncture', 'Alternative therapy for pain management and healing', 65.00),
(3, 'Nutritional Counseling', 'Expert advice on pet nutrition and diet planning', 40.00),
(3, 'Rehabilitation', 'Physical therapy and rehabilitation services', 75.00),
(3, 'Herbal Medicine', 'Natural remedies and supplements for pets', 50.00),
(4, 'Emergency Consultation', 'Immediate care for urgent medical conditions', 90.00),
(4, 'Critical Care', 'Intensive care for critically ill or injured pets', 150.00),
(4, 'Blood Transfusion', 'Emergency blood transfusion services', 200.00),
(4, 'Toxicity Treatment', 'Treatment for poisoning and toxic ingestion', 120.00)
ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    price = VALUES(price);

-- Insert pricing plans
INSERT INTO pricing_plans (name, price, description, features) VALUES
('Basic', 4990, 'Basic plan for pet services', 'Pet Feeding,Pet Grooming,Pet Training,Pet Exercise,Pet Treatment'),
('Standard', 9990, 'Standard plan with more features', 'All features in Basic,Enhanced Pet Services,Priority Support,Extended Medical Treatments'),
('Extended', 14900, 'Extended plan with all features', 'All features in Standard,Advanced Pet Services,Personalized Health Plans,24/7 Support,Customizable Pet Training,Exclusive Discounts on Products'); 