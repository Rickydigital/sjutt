<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
     <title>Student Portal - SJUT</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="{{ asset('app-assets/img/kaiadmin/favicon.ico') }}" type="image/x-icon" />

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-gradient-to-br from-purple-700 to-purple-900 flex items-center justify-center p-4">

    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-8">

        <!-- Logo -->
        <div class="flex justify-center mb-6">
            <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-20 w-20 object-contain">
        </div>

        <!-- Title -->
        <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">
            Welcome Back
        </h2>

        <!-- Error Messages -->
        @if ($errors->any())
            <div class="mb-4 text-red-600 text-sm">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>• {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Form -->
        <form method="POST" action="{{ route('stu.login.submit') }}">
            @csrf

            <!-- Email -->
            <div class="mb-4">
                <label for="reg_no" class="block text-gray-700 text-sm font-medium mb-1">Registration Number</label>
                <input id="reg_no"
                       type="text"
                       name="reg_no"
                       value="{{ old('reg_no') }}"
                       required
                       autofocus
                       class="w-full px-3 py-2 border rounded-lg focus:ring-purple-500 focus:border-purple-500">

            </div>

            <!-- Password -->
            <div class="mb-4">
                <label for="password" class="block text-gray-700 text-sm font-medium mb-1">Password</label>
                <input id="password"
                       type="password"
                       name="password"
                       required
                       class="w-full px-3 py-2 border rounded-lg focus:ring-purple-500 focus:border-purple-500">
            </div>

            <!-- Remember Me -->
            <div class="flex items-center mb-4">
                <input id="remember_me"
                       type="checkbox"
                       name="remember"
                       class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">

                <label for="remember_me" class="ml-2 text-sm text-gray-700">
                    Remember me
                </label>
            </div>

            <!-- Footer Row -->
            <div class="flex items-center justify-between">
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}"
                       class="text-sm text-purple-700 hover:underline">
                        Forgot password?
                    </a>
                @endif

                <button type="submit"
                        class="px-4 py-2 bg-purple-700 hover:bg-purple-800 text-white rounded-lg shadow">
                    Log in
                </button>
            </div>

        </form>
    </div>

</body>
</html>
