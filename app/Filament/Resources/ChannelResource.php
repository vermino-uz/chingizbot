<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChannelResource\Pages;
use App\Filament\Resources\ChannelResource\RelationManagers;
use App\Models\Channel;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ChannelResource extends Resource
{
    protected static ?string $model = Channel::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name'),
                TextInput::make('chat_id'),
                TextInput::make('link'),
                Forms\Components\DateTimePicker::make('created_at')
                    ->default(now()->timezone('Asia/Tashkent'))
                    ->displayFormat('Y-m-d H:i')
                    ->timezone('Asia/Tashkent')
                    ->hiddenOn('create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('chat_id'),
                Tables\Columns\TextColumn::make('link'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Qo\'shilgan vaqt')
                    ->dateTime()
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        $diff = $state->diff(now());
                        $parts = [];
                        
                        if ($diff->d > 0) $parts[] = $diff->d . ' kun';
                        if ($diff->h > 0) $parts[] = $diff->h . ' soat';
                        if ($diff->i > 0) $parts[] = $diff->i . ' daqiqa';
                        
                        return implode(' ', $parts) . ' oldin';
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\EditAction::make()->modalHeading('Edit Channel')->modalSubmitActionLabel('Save Changes'),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('updateChannel')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->form([
                        TextInput::make('name')
                            ->required()
                            ->default(fn (Channel $record): string => $record->name),
                        TextInput::make('chat_id')
                            ->required()
                            ->default(fn (Channel $record): string => $record->chat_id),
                        TextInput::make('link')
                            ->required()
                            ->default(fn (Channel $record): string => $record->link),
                    ])
                    ->action(function (Channel $record, array $data) {
                        $record->update($data);
                    })
                    ->requiresConfirmation(),
                Tables\Actions\ViewAction::make()
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    
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
            'index' => Pages\ListChannels::route('/'),
            'create' => Pages\CreateChannel::route('/create'),
            'edit' => Pages\EditChannel::route('/{record}/edit'),
        ];
    }
}
