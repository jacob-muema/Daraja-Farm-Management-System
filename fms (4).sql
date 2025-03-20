-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 20, 2025 at 10:13 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fms`
--

-- --------------------------------------------------------

--
-- Table structure for table `admintb`
--

CREATE TABLE `admintb` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admintb`
--

INSERT INTO `admintb` (`id`, `username`, `password`) VALUES
(1, 'farmermat', 'mat123');

-- --------------------------------------------------------

--
-- Table structure for table `contact`
--

CREATE TABLE `contact` (
  `name` varchar(30) NOT NULL,
  `email` text NOT NULL,
  `contact` varchar(10) NOT NULL,
  `message` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `contact`
--

INSERT INTO `contact` (`name`, `email`, `contact`, `message`) VALUES
('Anu', 'anu@gmail.com', '7896677554', 'Hey Admin'),
(' Viki', 'viki@gmail.com', '9899778865', 'Good Job, Pal'),
('Ananya', 'ananya@gmail.com', '9997888879', 'How can I reach you?'),
('Aakash', 'aakash@gmail.com', '8788979967', 'Love your site'),
('Mani', 'mani@gmail.com', '8977768978', 'Want some coffee?'),
('Karthick', 'karthi@gmail.com', '9898989898', 'Good service'),
('Abbis', 'abbis@gmail.com', '8979776868', 'Love your service'),
('Asiq', 'asiq@gmail.com', '9087897564', 'Love your service. Thank you!'),
('Jane', 'jane@gmail.com', '7869869757', 'I love your service!'),
('Jacob Muema', 'jacobmuema02@gmail.com', '0740491425', 'gtrtyyttygdfgf');

-- --------------------------------------------------------

--
-- Table structure for table `grocery_chatbot_messages`
--

CREATE TABLE `grocery_chatbot_messages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `response` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grocery_chatbot_messages`
--

INSERT INTO `grocery_chatbot_messages` (`id`, `user_id`, `username`, `message`, `response`, `created_at`) VALUES
(1, NULL, 'Jacob Muema', 'hello stuck at payment', 'We accept M-Pesa payments and Cash on Delivery. You can choose your preferred payment method at checkout.', '2025-03-20 20:26:49'),
(2, NULL, 'Jacob Muema', 'agent', 'Thank you for your message. Our team will get back to you soon.', '2025-03-20 20:27:00'),
(3, NULL, 'admin', 'hello stuck at payment', 'We accept M-Pesa payments and Cash on Delivery. You can choose your preferred payment method at checkout.', '2025-03-20 20:51:02'),
(4, NULL, 'Jacob Muema', 'can i buy oranges?', 'Thank you for your message. Our team will get back to you soon.', '2025-03-20 20:56:16');

-- --------------------------------------------------------

--
-- Table structure for table `grocery_orders`
--

CREATE TABLE `grocery_orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `delivery_address` text NOT NULL,
  `order_notes` text DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `order_status` varchar(50) DEFAULT 'Pending',
  `payment_method` varchar(50) NOT NULL,
  `payment_status` varchar(50) DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grocery_orders`
--

INSERT INTO `grocery_orders` (`id`, `user_id`, `customer_name`, `phone`, `delivery_address`, `order_notes`, `amount`, `order_date`, `order_status`, `payment_method`, `payment_status`) VALUES
(1, NULL, 'Jacob Muema', '0740491425', '90205', '', 320.00, '2025-03-20 18:27:49', 'Delivered', 'Cash on Delivery', 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `grocery_order_items`
--

CREATE TABLE `grocery_order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grocery_order_items`
--

INSERT INTO `grocery_order_items` (`id`, `order_id`, `product_id`, `product_name`, `price`, `quantity`, `subtotal`) VALUES
(1, 1, 10, 'Cooking Oil (1L)', 320.00, 1, 320.00);

-- --------------------------------------------------------

--
-- Table structure for table `grocery_products`
--

CREATE TABLE `grocery_products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) NOT NULL,
  `is_organic` tinyint(1) DEFAULT 0,
  `stock_quantity` int(11) DEFAULT 0,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grocery_products`
--

INSERT INTO `grocery_products` (`id`, `name`, `category`, `price`, `image`, `is_organic`, `stock_quantity`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Fresh Tomatoes', 'Vegetables', 120.00, 'https://images.unsplash.com/photo-1546094096-0df4bcaaa337?q=80&w=400', 1, 50, 'Organic fresh tomatoes from local farms', '2025-03-20 20:25:05', '2025-03-20 20:25:05'),
(2, 'Onions', 'Vegetables', 80.00, 'https://images.unsplash.com/photo-1580201092675-a0a6a6cafbb1?q=80&w=400', 0, 100, 'Fresh red and white onions', '2025-03-20 20:25:05', '2025-03-20 20:25:05'),
(4, 'Carrots', 'Vegetables', 90.00, 'https://images.unsplash.com/photo-1598170845058-32b9d6a5da37?q=80&w=400', 1, 70, 'Organic carrots rich in vitamins', '2025-03-20 20:25:05', '2025-03-20 20:25:05'),
(5, 'Bananas', 'Fruits', 120.00, 'https://images.unsplash.com/photo-1603833665858-e61d17a86224?q=80&w=400', 1, 60, 'Sweet organic bananas', '2025-03-20 20:25:05', '2025-03-20 20:25:05'),
(6, 'Apples', 'Fruits', 180.00, 'https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?q=80&w=400', 0, 40, 'Fresh red apples', '2025-03-20 20:25:05', '2025-03-20 20:25:05'),
(9, 'Wheat Flour (2kg)', 'Grains', 180.00, 'https://images.unsplash.com/photo-1603046891744-1f76eb10aec7?q=80&w=400', 0, 25, 'Fine wheat flour for baking', '2025-03-20 20:25:05', '2025-03-20 20:25:05'),
(10, 'Cooking Oil (1L)', 'Essentials', 320.00, 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?q=80&w=400', 0, 20, 'Pure vegetable cooking oil', '2025-03-20 20:25:05', '2025-03-20 20:25:05'),
(11, 'Sugar (1kg)', 'Essentials', 150.00, 'https://images.unsplash.com/photo-1581264692013-cf4e3e43ad04?q=80&w=400', 0, 40, 'Fine white sugar', '2025-03-20 20:25:05', '2025-03-20 20:25:05'),
(12, 'Salt (500g)', 'Essentials', 50.00, 'https://images.unsplash.com/photo-1518110925495-b37653f4d124?q=80&w=400', 0, 60, 'Iodized table salt', '2025-03-20 20:25:05', '2025-03-20 20:25:05'),
(13, 'mango', 'Fruits', 100.00, 'https://www.google.com/imgres?q=mango&imgurl=https%3A%2F%2Flistonic.com%2Fphimageproxy%2Flistonic%2Fproducts%2Fmango.webp&imgrefurl=https%3A%2F%2Flistonic.com%2Fp%2Fnutrition%2Fmango&docid=i0JUg6WhZ2UcBM&tbnid=BV0tKDjBjdmcbM&vet=12ahUKEwjkoKKtxJmMAxV8X_ED', 0, 30, 'new mango', '2025-03-20 20:49:52', '2025-03-20 20:49:52');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `delivery_address` text NOT NULL,
  `order_notes` text DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `order_status` varchar(50) DEFAULT 'Pending',
  `payment_method` varchar(50) NOT NULL,
  `payment_status` varchar(50) DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) NOT NULL,
  `is_organic` tinyint(1) DEFAULT 0,
  `stock_quantity` int(11) DEFAULT 0,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `userreg`
--

CREATE TABLE `userreg` (
  `pid` int(11) NOT NULL,
  `fname` varchar(20) NOT NULL,
  `lname` varchar(20) NOT NULL,
  `gender` varchar(10) NOT NULL,
  `email` varchar(30) NOT NULL,
  `contact` varchar(10) NOT NULL,
  `password` varchar(30) NOT NULL,
  `cpassword` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `userreg`
--

INSERT INTO `userreg` (`pid`, `fname`, `lname`, `gender`, `email`, `contact`, `password`, `cpassword`) VALUES
(1, 'Ram', 'Kumar', 'Male', 'ram@gmail.com', '9876543210', 'ram123', 'ram123'),
(2, 'Alia', 'Bhatt', 'Female', 'alia@gmail.com', '8976897689', 'alia123', 'alia123'),
(3, 'Shahrukh', 'khan', 'Male', 'shahrukh@gmail.com', '8976898463', 'shahrukh123', 'shahrukh123'),
(4, 'Kishan', 'Lal', 'Male', 'kishansmart0@gmail.com', '8838489464', 'kishan123', 'kishan123'),
(5, 'Gautam', 'Shankararam', 'Male', 'gautam@gmail.com', '9070897653', 'gautam123', 'gautam123'),
(6, 'Sushant', 'Singh', 'Male', 'sushant@gmail.com', '9059986865', 'sushant123', 'sushant123'),
(7, 'Nancy', 'Deborah', 'Female', 'nancy@gmail.com', '9128972454', 'nancy123', 'nancy123'),
(8, 'Kenny', 'Sebastian', 'Male', 'kenny@gmail.com', '9809879868', 'kenny123', 'kenny123'),
(9, 'William', 'Blake', 'Male', 'william@gmail.com', '8683619153', 'william123', 'william123'),
(10, 'Peter', 'Norvig', 'Male', 'peter@gmail.com', '9609362815', 'peter123', 'peter123'),
(11, 'Shraddha', 'Kapoor', 'Female', 'shraddha@gmail.com', '9768946252', 'shraddha123', 'shraddha123'),
(12, 'Jacob', 'Muema', 'Male', 'jacobmuema02@gmail.com', '0740491425', '123456', '123456'),
(13, 'Evans ', 'Muema', 'Male', 'evansndilinge111@gmail.com', '0740491425', '123456', '123456'),
(14, 'Jacob', 'Muema', 'Male', 'boysnappy182@gmail.com', '0740491425', '123456', '123456'),
(15, 'Jacob', 'Muema', 'Male', 'jmmunywoki5@masyopnet.com', '0740491425', '123456', '123456'),
(16, 'Jacob', 'Muema', 'Male', 'jmmunywoki6@masyopnet.com', '0740491425', '123456', '123456'),
(17, 'MATTANOH PATRIC', 'KILATYA', 'Male', 'mattanohpatric@gmail.com', '0792179877', '123456', '123456'),
(18, 'Jacob', 'Muema', 'Male', 'jacobmuema02@gmail.com', '0740491425', '123456', '123456'),
(19, 'Jacob', 'Muema', 'Male', 'jacobmuema02@gmail.com', '0740491425', '123456', '123456'),
(20, 'Jacob', 'Muema', 'Male', 'jacobmuema12@gmail.com', '0740491425', '0740491425', '0740491425');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('customer','admin') NOT NULL DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_admin` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `phone`, `address`, `role`, `created_at`, `is_admin`) VALUES
(1, 'admin', '$2y$10$YourHashedPasswordHere', 'admin@example.com', NULL, NULL, 'admin', '2025-03-15 08:18:29', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admintb`
--
ALTER TABLE `admintb`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `grocery_chatbot_messages`
--
ALTER TABLE `grocery_chatbot_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `grocery_orders`
--
ALTER TABLE `grocery_orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `grocery_order_items`
--
ALTER TABLE `grocery_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `grocery_products`
--
ALTER TABLE `grocery_products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `userreg`
--
ALTER TABLE `userreg`
  ADD PRIMARY KEY (`pid`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admintb`
--
ALTER TABLE `admintb`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `grocery_chatbot_messages`
--
ALTER TABLE `grocery_chatbot_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `grocery_orders`
--
ALTER TABLE `grocery_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `grocery_order_items`
--
ALTER TABLE `grocery_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `grocery_products`
--
ALTER TABLE `grocery_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `userreg`
--
ALTER TABLE `userreg`
  MODIFY `pid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `grocery_order_items`
--
ALTER TABLE `grocery_order_items`
  ADD CONSTRAINT `grocery_order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `grocery_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grocery_order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `grocery_products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
