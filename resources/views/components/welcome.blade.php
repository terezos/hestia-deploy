<div class="p-6 lg:p-8 bg-white border-b border-gray-200">
    <x-application-logo class="block h-12 w-auto" />

    <h1 class="mt-8 text-2xl font-medium text-gray-900">
        Welcome to Eight's Microservices
    </h1>

    <p class="mt-6 text-gray-500 leading-relaxed">
        Welcome to Eight’s Microservices – a robust suite of lightweight, scalable APIs designed to power modern eCommerce and business workflows.
        <br>
        Built with performance, modularity, and clarity in mind, each service is crafted to handle specific tasks efficiently, enabling seamless communication across systems.
    </p>
</div>

<div class="bg-gray-200 bg-opacity-25 grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8 p-6 lg:p-8">
    @role('admin')
    <div>
        <div class="flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" class="size-6 stroke-gray-400">
                <path stroke-linecap="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" />
            </svg>
            <h2 class="ms-3 text-xl font-semibold text-gray-900">
                <a href="/horizon">Horizon</a>
            </h2>
        </div>

        <p class="mt-4 text-gray-500 text-sm leading-relaxed">
            Laravel Horizon is a powerful dashboard and queue manager for Laravel applications. It provides real-time insights into job processing, including job throughput, failures, retries, and runtime performance. Horizon allows you to monitor and manage your Redis-powered queues with a clean and intuitive interface, making it ideal for teams running mission-critical background tasks.
        </p>

        <p class="mt-4 text-sm">
            <a href="/horizon" class="inline-flex items-center font-semibold text-indigo-700">
                Go to Dashboard

                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="ms-1 size-5 fill-indigo-500">
                    <path fill-rule="evenodd" d="M5 10a.75.75 0 01.75-.75h6.638L10.23 7.29a.75.75 0 111.04-1.08l3.5 3.25a.75.75 0 010 1.08l-3.5 3.25a.75.75 0 11-1.04-1.08l2.158-1.96H5.75A.75.75 0 015 10z" clip-rule="evenodd" />
                </svg>
            </a>
        </p>
    </div>
    @endrole
    <div>
        <div class="flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" class="w-6 h-6 stroke-gray-400">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 2C10.3431 2 9 3.34315 9 5V8C9 8.55228 9.44772 9 10 9H14C14.5523 9 15 8.55228 15 8V5C15 3.34315 13.6569 2 12 2Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.0711 14.0711L16 17.1421L10 11.1421L13.0711 8.07107C14.1136 7.02853 15.8864 7.02853 16.9289 8.07107L19.0711 10.2133C20.1136 11.2558 20.1136 13.0285 19.0711 14.0711Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 11L5 19L11 15L15 19L21 15L17 11H9Z" />
            </svg>
            <h2 class="ml-3 text-xl font-semibold text-gray-900">
                Deployments
            </h2>
        </div>

        <p class="mt-4 text-gray-500 text-sm leading-relaxed">
            Manage Deployments of your sites. Each deployment is isolated with its own database and configurations, ensuring that your site run smoothly and securely. Easily create, monitor, and manage your deployments to keep your applications up-to-date and performing at their best.
        </p>

        <p class="mt-4 text-sm">
            <a href="{{ route('sites.index') }}" class="inline-flex items-center font-semibold text-indigo-700">
                Go to Deployments

                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="ms-1 size-5 fill-indigo-500">
                    <path fill-rule="evenodd" d="M5 10a.75.75 0 01.75-.75h6.638L10.23 7.29a.75.75 0 111.04-1.08l3.5 3.25a.75.75 0 010 1.08l-3.5 3.25a.75.75 0 11-1.04-1.08l2.158-1.96H5.75A.75.75 0 015 10z" clip-rule="evenodd" />
                </svg>
            </a>
        </p>
    </div>
</div>
