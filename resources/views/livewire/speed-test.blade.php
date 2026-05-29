<div class="text-center px-4" x-data="speedTest()">

    <!-- Title -->
    <h1 class="text-3xl font-bold text-white mb-1">Speed Test</h1>
    <p class="text-gray-400 mb-10" x-text="statusText"></p>

    <!-- Gauge Circle -->
    <div class="relative w-48 h-48 mx-auto mb-10">
        <svg class="w-full h-full -rotate-90" viewBox="0 0 100 100">
            <circle cx="50" cy="50" r="45" fill="none" stroke="#1f2937" stroke-width="8"/>
            <circle cx="50" cy="50" r="45" fill="none" stroke="#3b82f6" stroke-width="8"
                stroke-dasharray="283"
                :stroke-dashoffset="283 - (283 * displaySpeed / 200)"
                style="transition: stroke-dashoffset 0.5s ease"/>
        </svg>
        <div class="absolute inset-0 flex flex-col items-center justify-center">
            <span class="text-4xl font-bold text-white" x-text="displaySpeed.toFixed(1)"></span>
            <span class="text-gray-400 text-sm">Mbps</span>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="flex justify-center gap-10 mb-10">
        <div>
            <p class="text-gray-500 text-xs uppercase tracking-widest">Ping</p>
            <p class="text-white text-xl font-semibold" x-text="ping ? ping + ' ms' : '--'"></p>
        </div>
        <div>
            <p class="text-gray-500 text-xs uppercase tracking-widest">Download</p>
            <p class="text-white text-xl font-semibold" x-text="download ? download + ' Mbps' : '--'"></p>
        </div>
        <div>
            <p class="text-gray-500 text-xs uppercase tracking-widest">Upload</p>
            <p class="text-white text-xl font-semibold" x-text="upload ? upload + ' Mbps' : '--'"></p>
        </div>
    </div>

    <!-- GO Button -->
    <button
        @click="startTest"
        :disabled="running"
        class="px-10 py-4 bg-blue-600 hover:bg-blue-500 disabled:bg-gray-700 disabled:cursor-not-allowed rounded-full text-xl font-bold text-white transition-all">
        <span x-text="running ? 'Testing...' : 'GO'"></span>
    </button>

    <script>
        function speedTest() {
            return {
                running: false,
                displaySpeed: 0,
                ping: null,
                download: null,
                upload: null,
                statusText: 'Press GO to begin',

                async startTest() {
                    this.running = true;
                    this.ping = null;
                    this.download = null;
                    this.upload = null;
                    this.displaySpeed = 0;

                    // Ping test
                    this.statusText = 'Testing ping...';
                    this.ping = await this.testPing();

                    // Download test
                    this.statusText = 'Testing download...';
                    this.download = await this.testDownload();

                    // Upload test
                    this.statusText = 'Testing upload...';
                    this.upload = await this.testUpload();

                    this.displaySpeed = parseFloat(this.download);
                    this.statusText = 'Test complete!';
                    this.running = false;
                },

                async testPing() {
                    const times = [];
                    for (let i = 0; i < 5; i++) {
                        const start = performance.now();
                        await fetch('/librespeed/empty.php?t=' + Date.now(), { method: 'GET' });
                        times.push(performance.now() - start);
                    }
                    return Math.min(...times).toFixed(0);
                },

                async testDownload() {
                    const duration = 6000;
                    const start = performance.now();
                    let totalBytes = 0;
                    const animate = setInterval(() => {
                        const elapsed = (performance.now() - start) / 1000;
                        if (elapsed > 0) {
                            this.displaySpeed = Math.min(((totalBytes * 8) / elapsed / 1_000_000), 200);
                        }
                    }, 200);

                    while (performance.now() - start < duration) {
                        const res = await fetch('/librespeed/garbage.php?ckSize=10&t=' + Math.random());
                        const buffer = await res.arrayBuffer();
                        totalBytes += buffer.byteLength;
                    }

                    clearInterval(animate);
                    const elapsed = (performance.now() - start) / 1000;
                    return ((totalBytes * 8) / elapsed / 1_000_000).toFixed(2);
                },

                async testUpload() {
                    const duration = 6000;
                    const start = performance.now();
                    let totalBytes = 0;
                    const chunkSize = 1 * 1024 * 1024;
                    const data = new Uint8Array(chunkSize);

                    const animate = setInterval(() => {
                        const elapsed = (performance.now() - start) / 1000;
                        if (elapsed > 0) {
                            this.displaySpeed = Math.min(((totalBytes * 8) / elapsed / 1_000_000), 200);
                        }
                    }, 200);

                    while (performance.now() - start < duration) {
                        await fetch('/librespeed/empty.php?t=' + Math.random(), {
                            method: 'POST',
                            body: data
                        });
                        totalBytes += chunkSize;
                    }

                    clearInterval(animate);
                    const elapsed = (performance.now() - start) / 1000;
                    return ((totalBytes * 8) / elapsed / 1_000_000).toFixed(2);
                }
            }
        }
    </script>
</div>