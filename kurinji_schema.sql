-- Kurinji Poultry Farm - MySQL Schema
CREATE DATABASE IF NOT EXISTS kurinji_poultry DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kurinji_poultry;

CREATE TABLE IF NOT EXISTS sales_egg (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sale_date DATE NOT NULL,
  particulars VARCHAR(255),
  qty DECIMAL(14,3),
  rate DECIMAL(10,4),
  amount DECIMAL(14,2) NOT NULL,
  INDEX (sale_date)
);

CREATE TABLE IF NOT EXISTS sales_feed (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sale_date DATE NOT NULL,
  particulars VARCHAR(255),
  qty DECIMAL(14,3),
  rate DECIMAL(10,4),
  amount DECIMAL(14,2) NOT NULL,
  INDEX (sale_date)
);

CREATE TABLE IF NOT EXISTS sales_culling (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sale_date DATE NOT NULL,
  particulars VARCHAR(255),
  qty_birds DECIMAL(12,2),
  rate DECIMAL(10,4),
  amount DECIMAL(14,2) NOT NULL,
  manure_kg_estimate DECIMAL(12,2),
  INDEX (sale_date)
);

CREATE TABLE IF NOT EXISTS sales_manure (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sale_date DATE NOT NULL,
  particulars VARCHAR(255),
  qty DECIMAL(14,3),
  rate DECIMAL(10,4),
  amount DECIMAL(14,2) NOT NULL,
  INDEX (sale_date)
);

CREATE TABLE IF NOT EXISTS sales_raw_material (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sale_date DATE NOT NULL,
  particulars VARCHAR(255),
  qty DECIMAL(14,3),
  rate DECIMAL(10,4),
  amount DECIMAL(14,2) NOT NULL,
  INDEX (sale_date)
);

CREATE TABLE IF NOT EXISTS sales_investment (
  id INT AUTO_INCREMENT PRIMARY KEY,
  inv_date DATE NOT NULL,
  particulars VARCHAR(255),
  amount DECIMAL(14,2) NOT NULL,
  INDEX (inv_date)
);

-- Expenses
CREATE TABLE IF NOT EXISTS exp_chick (
  id INT AUTO_INCREMENT PRIMARY KEY,
  purchase_date DATE NOT NULL,
  item VARCHAR(255),
  seller VARCHAR(255),
  qty_birds DECIMAL(12,2),
  rate DECIMAL(10,4),
  amount DECIMAL(14,2) NOT NULL,
  chick_count DECIMAL(12,2),
  INDEX (purchase_date)
);

CREATE TABLE IF NOT EXISTS exp_feed_ingredient (
  id INT AUTO_INCREMENT PRIMARY KEY,
  purchase_date DATE NOT NULL,
  category VARCHAR(50) NOT NULL,
  item VARCHAR(255),
  seller VARCHAR(255),
  qty_kg DECIMAL(14,3),
  rate DECIMAL(10,4),
  amount DECIMAL(14,2) NOT NULL,
  INDEX (purchase_date),
  INDEX (category)
);

CREATE TABLE IF NOT EXISTS exp_feeds (
  id INT AUTO_INCREMENT PRIMARY KEY,
  purchase_date DATE NOT NULL,
  item VARCHAR(255),
  seller VARCHAR(255),
  qty_kg DECIMAL(14,3),
  rate DECIMAL(10,4),
  amount DECIMAL(14,2) NOT NULL,
  INDEX (purchase_date)
);

CREATE TABLE IF NOT EXISTS exp_medicine (
  id INT AUTO_INCREMENT PRIMARY KEY,
  purchase_date DATE NOT NULL,
  item VARCHAR(255),
  seller VARCHAR(255),
  qty DECIMAL(14,3),
  rate DECIMAL(10,4),
  amount DECIMAL(14,2) NOT NULL,
  INDEX (purchase_date)
);

CREATE TABLE IF NOT EXISTS exp_salary (
  id INT AUTO_INCREMENT PRIMARY KEY,
  payment_date DATE NOT NULL,
  employee_name VARCHAR(255),
  qty DECIMAL(10,2),
  rate DECIMAL(10,4),
  amount DECIMAL(14,2) NOT NULL,
  INDEX (payment_date),
  INDEX (employee_name(100))
);

CREATE TABLE IF NOT EXISTS exp_labour (
  id INT AUTO_INCREMENT PRIMARY KEY,
  work_start DATE,
  work_end DATE,
  worker_name VARCHAR(255),
  days DECIMAL(8,2),
  wages_per_day DECIMAL(10,4),
  amount DECIMAL(14,2) NOT NULL,
  INDEX (work_start)
);

CREATE TABLE IF NOT EXISTS exp_rent (
  id INT AUTO_INCREMENT PRIMARY KEY,
  payment_date DATE NOT NULL,
  place VARCHAR(255),
  rate DECIMAL(10,4),
  amount DECIMAL(14,2) NOT NULL,
  INDEX (payment_date)
);

CREATE TABLE IF NOT EXISTS exp_asset (
  id INT AUTO_INCREMENT PRIMARY KEY,
  purchase_date DATE NOT NULL,
  item VARCHAR(255),
  seller VARCHAR(255),
  qty DECIMAL(12,3),
  rate DECIMAL(12,4),
  amount DECIMAL(14,2) NOT NULL,
  INDEX (purchase_date)
);

CREATE TABLE IF NOT EXISTS exp_misc (
  id INT AUTO_INCREMENT PRIMARY KEY,
  expense_date DATE NOT NULL,
  item VARCHAR(255),
  description VARCHAR(500),
  qty DECIMAL(12,3),
  rate DECIMAL(10,4),
  amount DECIMAL(14,2) NOT NULL,
  INDEX (expense_date),
  INDEX (item(100))
);

CREATE TABLE IF NOT EXISTS exp_accruals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  accrual_date DATE,
  item VARCHAR(255),
  seller VARCHAR(255),
  qty DECIMAL(12,3),
  rate DECIMAL(10,4),
  amount DECIMAL(14,2) NOT NULL
);

-- Loans
CREATE TABLE IF NOT EXISTS loan_lender (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lender_name VARCHAR(255) NOT NULL UNIQUE,
  lender_type ENUM('individual','finance_company','chit','overdraft','emi','partner') NOT NULL,
  is_closed TINYINT(1) DEFAULT 0
);

CREATE TABLE IF NOT EXISTS loan_transaction (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lender_id INT NOT NULL,
  txn_date DATE NOT NULL,
  loan_availed DECIMAL(14,2) DEFAULT 0,
  balance DECIMAL(14,2),
  interest_pct DECIMAL(8,4),
  interest_amount DECIMAL(14,2),
  amount_paid DECIMAL(14,2),
  FOREIGN KEY (lender_id) REFERENCES loan_lender(id),
  INDEX (txn_date),
  INDEX (lender_id)
);
