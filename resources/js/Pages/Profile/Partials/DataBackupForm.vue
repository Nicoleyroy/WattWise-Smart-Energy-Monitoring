<script setup>
import { DownloadCloud } from 'lucide-vue-next';
import { ref } from 'vue';

const isDownloading = ref(false);
const backupMessage = ref('');

const downloadBackup = async () => {
    isDownloading.value = true;
    backupMessage.value = 'Preparing your data... This may take a few seconds.';
    
    try {
        window.location.href = route('profile.backup');
        
        // Reset message after a short delay since we can't easily track standard browser downloads 
        // without complex fetch blob logic. Window.location.href is reliable for downloads.
        setTimeout(() => {
            isDownloading.value = false;
            backupMessage.value = 'Backup processed successfully.';
            setTimeout(() => backupMessage.value = '', 3000);
        }, 2000);
    } catch (error) {
        console.error('Backup error:', error);
        backupMessage.value = 'An error occurred during backup.';
        isDownloading.value = false;
    }
};
</script>

<template>
    <section class="space-y-6">
        <header class="mb-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                Data Backup
            </h2>

            <p class="mt-2 text-sm text-gray-700 dark:text-gray-400 font-medium">
                Export all your account data, including energy readings, device schedules, and maintenance alerts.
                The data will be downloaded as a portable JSON file.
            </p>
        </header>

        <div class="bg-cyan-50/50 dark:bg-cyan-900/20 border border-cyan-100 dark:border-cyan-900 rounded-xl p-6">
            <div class="flex items-start gap-4">
                <div class="p-3 bg-cyan-100 dark:bg-cyan-900/50 text-cyan-600 dark:text-cyan-400 rounded-lg shrink-0">
                    <DownloadCloud class="w-6 h-6" />
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">Export Your Data</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 mb-4">
                        Download a comprehensive backup of your system data. Save this file in a secure location.
                    </p>
                    <button
                        @click="downloadBackup"
                        :disabled="isDownloading"
                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-cyan-600 text-white rounded-lg hover:bg-cyan-500 transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed shadow-sm"
                    >
                        <DownloadCloud class="w-4 h-4" />
                        {{ isDownloading ? 'Preparing Backup...' : 'Download Backup JSON' }}
                    </button>
                    
                    <p v-if="backupMessage" 
                       class="mt-3 text-sm font-medium" 
                       :class="backupMessage.includes('error') ? 'text-red-600' : 'text-cyan-600'">
                        {{ backupMessage }}
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>
