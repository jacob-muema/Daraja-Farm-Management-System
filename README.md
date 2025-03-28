
# 🌿 FreshMarket - Farm Management System  

![FreshMarket Banner](https://github.com/user-attachments/assets/e4f7ad5a-b236-459a-9e65-784750335ecb)  

## 📋 Overview  

FreshMarket is an advanced **Farm Management System** that connects farmers directly with consumers. It enables users to browse and purchase **fresh** fruits and vegetables online, offering convenient payment methods, including **M-Pesa integration** via the Daraja API and **Cash on Delivery**.  

## ✨ Features  

- **User-Friendly Interface** 🖥️ – A clean, modern design for easy navigation  
- **Product Catalog** 📦 – Categorized display of fruits, vegetables, and organic produce  
- **Shopping Cart** 🛒 – Add, remove, and update product quantities  
- **Secure Payment Options** 💳  
  - **M-Pesa integration** via Daraja API  
  - **Cash on Delivery**  
- **Order Management** 📦  
  - Order tracking for customers  
  - **Admin panel** for order processing and status updates  
- **User Authentication** 🔒 – Secure login and registration system  
- **Responsive Design** 📱 – Works seamlessly on desktops, tablets, and mobile devices  

## 🛠️ Technologies Used  

- **Frontend:** HTML5, CSS3, JavaScript, Bootstrap 4  
- **Backend:** PHP, MySQL  
- **Payment Integration:** Safaricom Daraja API (M-Pesa)  
- **Other Libraries:** jQuery, Font Awesome  

---

## 📥 Installation  

### 1️⃣ Clone the Repository  

```bash
git clone https://github.com/jacob-muema/Daraja-Farm-Management-System
cd freshmarket
```  

### 2️⃣ Database Setup  

1. Create a MySQL database named `fms`  
2. Import the `database_setup.sql` file to create the necessary tables  

```bash
mysql -u username -p fms < database_setup.sql
```  

### 3️⃣ Web Server Configuration  

1. Deploy the files to your web server directory (e.g., `htdocs` for XAMPP)  
2. Ensure PHP is configured correctly on your server  

### 4️⃣ Daraja API Configuration  

1. **Register for a Daraja API account** at [Safaricom Developer Portal](https://developer.safaricom.co.ke/)  
2. Create a new app to get your **Consumer Key** and **Consumer Secret**  
3. Update `mpesa-payment.php` with your credentials:  

```php
$consumerKey = "YOUR_CONSUMER_KEY";
$consumerSecret = "YOUR_CONSUMER_SECRET";
$shortcode = "YOUR_SHORTCODE";
$passkey = "YOUR_PASSKEY";
```

---

## ⚙️ Configuration  

### 🔹 M-Pesa Daraja API Setup  

#### ✅ Register for Daraja API  

1. Visit [Safaricom Developer Portal](https://developer.safaricom.co.ke/)  
2. Create an account and log in  
3. Create a new app to get your API credentials  

#### ✅ Configure Callback URL  

1. Set up your callback URL in the Daraja dashboard  
2. This should point to:  
   ```
   https://your-domain.com/api/mpesa/callback/route.php
   ```

#### ✅ Testing in Sandbox  

1. Use the **sandbox environment** for testing before going live  
2. Test phone number format: `254XXXXXXXXX`  
3. Default test credentials are provided in the code  

#### ✅ Going Live  

1. Request **Go-Live** on the Daraja portal  
2. Update API endpoints from **sandbox** to **production URLs**  
3. Replace **test credentials** with **production credentials**  

---

## 🚀 Usage  

### 🛍️ **Customer Flow**  

1. Browse products by category  
2. Add items to cart  
3. Proceed to checkout  
4. Choose payment method (**M-Pesa** or **Cash on Delivery**)  
5. Complete order  
6. Track order status  

### 👨‍💼 **Admin Flow**  

1. Log in with **admin credentials**:  
   ```
   Username: farmermat  
   Password: mat123  
   ```
2. View all orders in the **admin panel**  
3. Update order statuses (**Pending → Processing → Delivered**)  
4. Manage products and inventory  

---

## 📸 Screenshots  

### 🌍 Homepage  
![Homepage](https://github.com/user-attachments/assets/e4f7ad5a-b236-459a-9e65-784750335ecb)  

### 🛍️ Product Catalog  
![Product Catalog](https://github.com/user-attachments/assets/c9418dc1-ff56-4ff9-a595-d9ac20c44fd7)  

### 🛒 Shopping Cart  
![Shopping Cart](https://github.com/user-attachments/assets/57d32289-0f43-4e22-abb7-929efba2ec12)  

### 💰 M-Pesa Payment  
![M-Pesa Payment](https://github.com/user-attachments/assets/eff7d8aa-cd75-4565-a8cc-1c2dd5448509)  

### 📲 M-Pesa Mobile Popup  
![M-Pesa Mobile Popup](https://github.com/user-attachments/assets/5df37959-2db2-4c60-bacb-ce23abc7a126)  

### 🔧 Admin Dashboard  
![Admin Dashboard](https://github.com/user-attachments/assets/effc3030-26fd-4b1e-be77-a22dc0768a2b)  

---

## 🤝 Contributing  

Contributions are **welcome**! Follow these steps:  

1. **Fork** the repository  
2. **Create** your feature branch (`git checkout -b feature/AmazingFeature`)  
3. **Commit** your changes (`git commit -m 'Add some AmazingFeature'`)  
4. **Push** to the branch (`git push origin feature/AmazingFeature`)  
5. **Open a Pull Request**  

---

## 📄 License  

This project is licensed under the **MIT License** – see the [LICENSE](LICENSE) file for details.  

---

## 📞 Contact  

👨‍💻 **Developer:** **Jacob Muema**  
📧 Email: [jacobmuema02@gmail.com](mailto:jacobmuema02@gmail.com)  
📞 Phone: +254740491425  
🌍 GitHub: [Jacob Muema](https://github.com/jacob-muema)  
📌 Project Link: [FreshMarket on GitHub](https://github.com/jacob-muema/Daraja-Farm-Management-System)  

---

## 🙏 Acknowledgements  

- **[Safaricom Daraja API](https://developer.safaricom.co.ke/)** for M-Pesa integration  
- **[Bootstrap](https://getbootstrap.com/)** for UI styling  
- **[Font Awesome](https://fontawesome.com/)** for icons  
- **[Unsplash](https://unsplash.com/)** for product images  

---

## 🌎 Environment Variables  

This project requires the following **environment variables** for M-Pesa integration:  

```bash
MPESA_SHORTCODE=YOUR_MPESA_SHORTCODE
MPESA_PASSKEY=YOUR_MPESA_PASSKEY
MPESA_CALLBACK_URL=https://your-domain.com/api/mpesa/callback/route.php
MPESA_CONSUMER_KEY=YOUR_CONSUMER_KEY
MPESA_CONSUMER_SECRET=YOUR_CONSUMER_SECRET
```

---

🔥 **FreshMarket is here to revolutionize farm-to-table shopping!** 🌱🚀  
```

