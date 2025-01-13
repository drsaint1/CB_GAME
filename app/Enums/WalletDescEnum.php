<?php 

namespace App\Enums;

enum WalletDescEnum: string {
	case CUSTOMER_DELIVERY = "User Make Delivery";
	case WALLET_DEBIT = "User Pay Wallet";
	case WALLET_CREDIT = "User Receive Money";
	case CASH_DEDIT = "User Pay Cash";
	case KOBO_CASH_CREDIT = "Logistics to Pay";
}