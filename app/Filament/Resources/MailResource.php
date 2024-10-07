<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MailResource\Pages;
use App\Filament\Resources\MailResource\RelationManagers;
use App\Models\Mail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Notifications;
use Filament\Tables;
use App\Jobs\SendTelegramMessageJob;
use Illuminate\Support\Facades\DB;
use App\Models\ActiveUser;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Actions\SendBulkTelegramMessage;
use Illuminate\Support\Facades\Log;

class MailResource extends Resource
{
    protected static ?string $model = Mail::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Textarea::make('text')
                    ->rows(3)
                    ->columns(20),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('text')
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('sendBulkMessage')
                    ->label('Send Bulk Message')
                    ->form([
                        Forms\Components\Textarea::make('message')
                            ->label('Message')
                            ->required()
                            ->rows(3)
                    ])
                    ->action(function (array $data, Mail $record) {
                        try {
                            $botToken = env('TELEGRAM_BOT_TOKEN');
                            $message = $data['message'];

                            $sendBulkMessage = new SendBulkTelegramMessage();
                            $sendBulkMessage->execute($message, $botToken);

                            // Log success
                            Log::info('Bulk message sent successfully');

                            return Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Message Sent')
                                ->body('The bulk message has been sent successfully.');

                        } catch (\Exception $e) {
                            // Log the error
                            Log::error('Error sending bulk message: ' . $e->getMessage());

                            return Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('An error occurred while sending the message. Please try again.');
                        }
                    })
                    ->requiresConfirmation()
                    ->icon('heroicon-o-paper-airplane'),
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
            'index' => Pages\ListMails::route('/'),
            'create' => Pages\CreateMail::route('/create'),
            'edit' => Pages\EditMail::route('/{record}/edit'),
        ];
    }
}
