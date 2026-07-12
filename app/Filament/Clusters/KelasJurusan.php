<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class KelasJurusan extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Kelas & Jurusan';

    protected static ?int $navigationSort = 20;
}
