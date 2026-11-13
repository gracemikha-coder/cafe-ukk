<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | Kopi Senja</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex items-center justify-center bg-[#f7f4f1] font-[Arial,sans-serif]">

    <div class="w-full max-w-[490px] bg-white border-2 border-[#e2ddd7] px-12 py-12 mx-4">

        <h1 class="text-center text-[25px] font-bold text-[#60442f]">KOPI SENJA</h1>
        <h2 class="text-center text-[22px] font-bold text-[#29231f] mt-5">Login</h2>
        <p class="text-center text-[14px] text-[#877c72] mt-3">
            Masuk untuk mengelola operasional kedai
        </p>

        <form method="POST" action="/login" class="mt-10">
            @csrf

            @error('username')
                <p class="text-red-600 text-sm mb-4">{{ $message }}</p>
            @enderror

            <label class="block text-[14px] text-[#51463d] mb-[6px]">Email / Username</label>
            <input type="text" name="username" value="{{ old('username') }}"
                   placeholder="Masukkan email atau username" autofocus
                   class="w-full h-[42px] px-[14px] text-[14px] bg-white border-[1.5px] border-[#d8d2cb] placeholder-[#9a9189] outline-none focus:border-[#6f4e37]">

            <label class="block text-[14px] text-[#51463d] mt-5 mb-[6px]">Kata sandi</label>
            <input type="password" name="password" placeholder="••••••••"
                   class="w-full h-[42px] px-[14px] text-[14px] bg-white border-[1.5px] border-[#d8d2cb] placeholder-[#9a9189] outline-none focus:border-[#6f4e37]">

            <button type="submit"
                    class="w-full h-[44px] mt-10 bg-[#6f4e37] border-2 border-[#6f4e37] text-white text-[15px] hover:bg-[#5c402d] cursor-pointer">
                Masuk
            </button>
        </form>

        <p class="text-center text-[13px] text-[#877c72] mt-8">
            Lupa kata sandi? Hubungi administrator.
        </p>
    </div>

</body>
</html>