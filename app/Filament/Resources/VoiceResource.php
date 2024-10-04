<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VoiceResource\Pages;
use App\Filament\Resources\VoiceResource\RelationManagers;
use App\Models\Voice;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use App\Models\Admin;
use Illuminate\Support\Facades\Http;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VoiceResource extends Resource
{
    protected static ?string $model = Voice::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Name')
                    ->required(),
                TextInput::make('file_id')
                    ->label('File ID')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id'),
                TextColumn::make('name')->searchable(),
                TextColumn::make('uses'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('sendAudio')
                    ->label('Send Audio')
                    ->icon('heroicon-o-paper-airplane')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Select::make('admin_id')
                            ->label('Select Admin')
                            ->options(Admin::pluck('name', 'chat_id'))
                            ->required()->native(false)
                    ])
                    ->action(function (Voice $record, array $data) {
                        $json = [
                            'message' => [
                                'message_id' => 1793158,
                                'from' => [
                                    'id' => $data['admin_id'],
                                ],
                                'chat' => [
                                    'id' => $data['admin_id'],
                                ],
                                'text' => '/' . $record->id,
                            ]
                        ];
                        try {
                            $response = Http::post('https://chingizbot.botproject.uz/api/telegra/webhook', $json);
                            Notification::make()
                                ->title('Audio sent successfully')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Log::error('Failed to send audio: ' . $e->getMessage());
                            Notification::make()
                                ->title('Failed to send audio')
                                ->danger()
                                ->send();
                        }
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
            'index' => Pages\ListVoices::route('/'),
            'create' => Pages\CreateVoice::route('/create'),
            'edit' => Pages\EditVoice::route('/{record}/edit'),
        ];
    }
}
