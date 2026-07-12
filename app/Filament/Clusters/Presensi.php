<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Presensi extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Presensi';

    protected static ?int $navigationSort = 30;
}
