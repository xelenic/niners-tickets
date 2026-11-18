<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('app:create-admin-user {--name= : The admin\'s name} {--email= : The admin\'s email address}')]
#[Description('Create a user who can log in to the raffle admin panel')]
class CreateAdminUser extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $attributes = [
            'name' => $this->option('name') ?: text(label: 'Name', required: true),
            'email' => Str::lower(trim($this->option('email') ?: text(label: 'Email', required: true))),
            'password' => password(label: 'Password', required: true, hint: 'At least 8 characters.'),
        ];

        $validator = Validator::make($attributes, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', Password::min(8)],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $user = new User($attributes);
        $user->is_admin = true;
        $user->save();

        $this->components->info("Admin user [{$user->email}] created. Log in at ".route('admin.login'));

        return self::SUCCESS;
    }
}
