
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | CareLink</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-100 flex items-center justify-center p-4">

    <main class="bg-white shadow-lg rounded-xl p-8 w-full max-w-md">

        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-blue-800">
                CareLink
            </h1>

            <p class="text-gray-600 mt-2">
                Patient Follow-Up Management
            </p>
        </div>

        <h2 class="text-xl font-semibold mb-6">
            Sign in to your account
        </h2>

        <form action="{{ route('login') }}" method="POST">
            @csrf

            <div class="mb-4">
                <label for="email" class="block mb-2 font-medium">
                    Email Address
                </label>

                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    autocomplete="username"
                    required
                    aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                    aria-describedby="{{ $errors->has('email') ? 'email-error' : '' }}"
                    class="w-full border border-gray-300 rounded-lg p-3"
                    placeholder="Enter your email"
                >

                @error('email')
                    <p id="email-error" role="alert"
                       class="text-red-600 text-sm mt-1">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div class="mb-6">
                <label for="password" class="block mb-2 font-medium">
                    Password
                </label>

                <input
                    id="password"
                    type="password"
                    name="password"
                    autocomplete="current-password"
                    required
                    class="w-full border border-gray-300 rounded-lg p-3"
                    placeholder="Enter your password"
                >

                @error('password')
                    <p role="alert" class="text-red-600 text-sm mt-1">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <button
                type="submit"
                class="w-full bg-blue-700 hover:bg-blue-800
                       text-white py-3 rounded-lg font-semibold
                       focus-visible:outline-2 focus-visible:outline-offset-2
                       focus-visible:outline-blue-700"
            >
                Sign In
            </button>
        </form>

        <p class="text-center text-gray-500 text-sm mt-8">
            CareLink Follow-Up | Assessment Demo
        </p>

    </main>

</body>
</html>
