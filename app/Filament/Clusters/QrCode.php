<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class QrCode extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $navigationLabel = 'QR Code';

    protected static ?int $navigationSort = 40;
}
