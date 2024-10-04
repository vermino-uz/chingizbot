<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdResource\Pages;
use App\Filament\Resources\AdResource\RelationManagers;
use App\Models\Ad;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AdResource extends Resource
{
    protected static ?string $model = Ad::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')->readonly(),
                Textarea::make('text'),
                
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->width('30')->limit(40)->wrap(),
                TextColumn::make('text'),
                TextColumn::make('updated_at')
                    ->label('Reklama vaqti')
                    ->date('d.m.Y H:i:s')
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        $now = now();
                        $diff = $state->diff($now);
                        $days = $diff->days;
                        $hours = $diff->h;
                        $minutes = $diff->i;
                        
                        $result = [];
                        if ($days > 0) {
                            $result[] = $days . ' ' . ($days == 1 ? 'kun' : 'kunlar');
                        }
                        if ($hours > 0) {
                            $result[] = $hours . ' ' . ($hours == 1 ? 'soat' : 'soatu');
                        }
                        if ($minutes > 0) {
                            $result[] = $minutes . ' ' . ($minutes == 1 ? 'minut' : 'minut');
                        }
                        
                        return implode(' ', $result);
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('editText')
                    ->label('Edit Text')
                    ->icon('heroicon-o-pencil')
                    ->modalHeading('Edit Ad Text')
                    ->modalSubmitActionLabel('Save')
                    ->modalWidth('md')
                    ->form([
                        Textarea::make('text')
                            ->label('Ad Text')
                            ->rows(5)
                            ->default(fn (Ad $record) => $record->text)
                    ])
                    ->action(function (Ad $record, array $data): void {
                        $record->update([
                            'text' => $data['text'],
                        ]);
                    }),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
            
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAds::route('/'),
            'create' => Pages\CreateAd::route('/create'),
            'edit' => Pages\EditAd::route('/{record}/edit'),
        ];
    }
}
