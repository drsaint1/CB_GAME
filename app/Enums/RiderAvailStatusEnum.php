<?php

namespace App\Enums;

enum RiderAvailStatusEnum: string {
	case BUSY = 'busy';
	case AWAY = 'away';
	case AVAILABLE = 'available';
}