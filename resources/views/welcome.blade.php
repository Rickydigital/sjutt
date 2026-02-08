<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SJUT | University Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .bg-sjut { background-color: #0f172a; } /* Deep Navy */
        .glass-effect { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4 md:p-10">

    <div class="max-w-5xl w-full bg-white rounded-3xl shadow-2xl overflow-hidden flex flex-col md:flex-row min-h-[600px]">
        
        <div class="md:w-1/2 bg-sjut relative flex flex-col items-center justify-center text-white p-12 overflow-hidden">
            <div class="absolute top-0 right-0 -mr-20 -mt-20 w-64 h-64 bg-blue-500 rounded-full opacity-10"></div>
            <div class="absolute bottom-0 left-0 -ml-20 -mb-20 w-80 h-80 bg-blue-700 rounded-full opacity-10"></div>

            <div class="relative z-10 text-center">
                <div class="flex justify-center mb-8">
                    <div class="bg-white p-4 rounded-2xl shadow-xl transition-transform hover:scale-105">
                        <img src="{{ asset('images/logo.png') }}" alt="SJUT Logo" class="h-24 w-24 object-contain">
                    </div>
                </div>
                
                <h1 class="text-3xl md:text-4xl font-bold tracking-tight mb-4">
                    ST. JOHN'S UNIVERSITY <br> <span class="text-blue-400">OF TANZANIA</span>
                </h1>
                <p class="text-blue-100 text-lg font-light italic">"Learn to Serve"</p>
                
                
            </div>
        </div>

        <div class="md:w-1/2 p-8 md:p-16 flex flex-col justify-center bg-white">
            <div class="mb-10">
                <h2 class="text-3xl font-bold text-gray-800">University Portal</h2>
                <p class="text-gray-500 mt-2">Please select your destination to continue</p>
            </div>

            <div class="grid gap-6">
                
                <a href="{{ route('stu.login') }}" class="group relative overflow-hidden p-6 border-2 border-gray-100 rounded-2xl hover:border-blue-600 hover:shadow-lg transition-all duration-300">
                    <div class="flex items-center space-x-5">
                        <div class="bg-blue-100 text-blue-600 p-4 rounded-xl group-hover:bg-blue-600 group-hover:text-white transition-colors duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path d="M12 14l9-5-9-5-9 5 9 5z" />
                                <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-800 group-hover:text-blue-600">Student Portal</h3>
                           
                        </div>
                        <div class="text-gray-300 group-hover:text-blue-600 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </a>

                <a href="{{ route('login') }}" class="group relative overflow-hidden p-6 border-2 border-gray-100 rounded-2xl hover:border-blue-600 hover:shadow-lg transition-all duration-300">
                    <div class="flex items-center space-x-5">
                        <div class="bg-gray-100 text-gray-600 p-4 rounded-xl group-hover:bg-blue-600 group-hover:text-white transition-colors duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-800 group-hover:text-blue-600">Staff & Admin</h3>
                            
                        </div>
                        <div class="text-gray-300 group-hover:text-blue-600 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </a>

            </div>

            <div class="mt-12 pt-6 border-t border-gray-100 flex justify-between items-center text-xs text-gray-400">
                <p>&copy; 2026 SJUT ICT Department</p>
                <div class="flex space-x-4">
                    <a href="#" class="hover:underline">Help Desk</a>
                    <a href="#" class="hover:underline">Privacy Policy</a>
                </div>
            </div>
        </div>
    </div>

</body>
</html>