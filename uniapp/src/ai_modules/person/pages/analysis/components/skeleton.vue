<template>
    <view class="h-full space-y-3">
        <view class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F3F4F6]">
            <view class="flex justify-between items-center mb-4 pb-4 border-[0] border-b border-solid border-[#F9FAFB]">
                <view>
                    <view class="skeleton-box w-20 h-3 mb-2"></view>
                    <view class="skeleton-box w-32 h-5"></view>
                </view>
                <view class="skeleton-circle w-10 h-10"></view>
            </view>
            <view>
                <view class="flex items-center mb-3">
                    <view class="skeleton-box w-16 h-3 mr-2"></view>
                    <view class="skeleton-box w-20 h-6"></view>
                </view>
                <view class="flex flex-wrap gap-2 mb-4">
                    <view class="skeleton-box w-24 h-6"></view>
                    <view class="skeleton-box w-20 h-6"></view>
                    <view class="skeleton-box w-28 h-6"></view>
                </view>
                <view class="skeleton-box w-full h-16 rounded-xl"></view>
            </view>
        </view>

        <view class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F3F4F6]">
            <view class="flex items-center mb-4">
                <view class="skeleton-circle w-7 h-7 mr-3"></view>
                <view class="skeleton-box w-40 h-4"></view>
            </view>
            <view class="skeleton-box w-full h-12 rounded-xl mb-4"></view>
            <view class="flex flex-wrap gap-2 mb-4">
                <view class="skeleton-box w-24 h-6 rounded-full"></view>
                <view class="skeleton-box w-32 h-6 rounded-full"></view>
                <view class="skeleton-box w-20 h-6 rounded-full"></view>
                <view class="skeleton-box w-28 h-6 rounded-full"></view>
            </view>
            <view class="skeleton-box w-full h-16 rounded-lg"></view>
        </view>

        <view class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F3F4F6]">
            <view class="flex items-center mb-5">
                <view class="skeleton-circle w-7 h-7 mr-3"></view>
                <view class="skeleton-box w-32 h-4"></view>
            </view>
            <view class="space-y-4 relative">
                <view class="absolute left-6 top-4 bottom-4 w-0.5 bg-[#F3F4F6] z-0"></view>

                <view v-for="n in 4" :key="n" class="relative z-10 flex items-start pb-4">
                    <view class="skeleton-circle w-12 h-12 shrink-0 mr-4"></view>
                    <view class="pt-1 flex-1">
                        <view class="flex items-baseline mb-2">
                            <view class="skeleton-box w-16 h-4 mr-2"></view>
                            <view class="skeleton-box w-12 h-4"></view>
                        </view>
                        <view class="skeleton-box w-full h-3"></view>
                    </view>
                </view>
            </view>
        </view>
        <view class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F3F4F6]">
            <view class="flex items-center justify-between mb-4">
                <view class="flex items-center">
                    <view class="skeleton-circle w-7 h-7 mr-3"></view>
                    <view class="skeleton-box w-32 h-4"></view>
                </view>
                <view class="skeleton-box w-20 h-6 rounded"></view>
            </view>
            <view class="skeleton-box w-full h-8 mb-4"></view>

            <view class="flex overflow-x-auto pb-2 mb-4 space-x-2">
                <view v-for="n in 5" :key="n" class="skeleton-box shrink-0 w-20 h-8 rounded-xl"></view>
            </view>

            <view class="space-y-4">
                <view
                    v-for="n in 3"
                    :key="n"
                    class="bg-[#F9FAFB] rounded-xl border border-solid border-[#F3F4F6] overflow-hidden">
                    <view class="px-4 py-2.5 flex justify-between items-center bg-[#F3F4F6]">
                        <view class="skeleton-box w-20 h-3"></view>
                        <view class="skeleton-box w-12 h-5 rounded"></view>
                    </view>
                    <view class="p-4">
                        <view class="flex items-center mb-3">
                            <view class="skeleton-circle w-6 h-6 mr-2"></view>
                            <view class="skeleton-box w-16 h-4"></view>
                        </view>
                        <view class="space-y-2.5">
                            <view v-for="m in 3" :key="m" class="flex items-start">
                                <view class="skeleton-box w-12 h-5 mr-3 rounded"></view>
                                <view class="skeleton-box flex-1 h-8"></view>
                            </view>
                        </view>
                    </view>
                </view>
            </view>
        </view>

        <view class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F3F4F6]">
            <view class="skeleton-box w-32 h-4 mx-auto mb-4"></view>
            <view class="grid grid-cols-2 gap-3">
                <view class="bg-[#F9FAFB] rounded-xl p-3 border border-solid border-[#F3F4F6] text-center">
                    <view class="skeleton-box w-20 h-3 mx-auto mb-2"></view>
                    <view class="skeleton-box w-16 h-4 mx-auto mb-2"></view>
                    <view class="skeleton-box w-24 h-3 mx-auto"></view>
                </view>
                <view class="bg-[#FFF7ED] rounded-xl p-3 border border-solid border-[#FED7AA] text-center">
                    <view class="skeleton-box w-20 h-3 mx-auto mb-2"></view>
                    <view class="skeleton-box w-16 h-4 mx-auto mb-2"></view>
                    <view class="skeleton-box w-24 h-3 mx-auto"></view>
                </view>
            </view>
        </view>
        <view class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F3F4F6] text-center">
            <view class="skeleton-box w-24 h-3 mx-auto mb-4"></view>
            <view class="grid grid-cols-3 gap-2 mb-4">
                <view
                    v-for="n in 3"
                    :key="n"
                    class="bg-[#F9FAFB] p-2.5 rounded-xl border border-solid border-[#F3F4F6]">
                    <view class="skeleton-box w-12 h-4 mx-auto mb-1"></view>
                    <view class="skeleton-box w-8 h-3 mx-auto"></view>
                </view>
            </view>
            <view class="border-[0] border-t border-solid border-[#F3F4F6] pt-4">
                <view class="skeleton-box w-32 h-8 mx-auto mb-2"></view>
                <view class="skeleton-box w-40 h-6 mx-auto rounded-full"></view>
            </view>
        </view>

        <view class="rounded-2xl p-6 shadow-lg relative overflow-hidden bg-[#F3F4F6]">
            <view class="flex justify-between items-center mb-6 bg-[#E5E7EB]/50 p-3 rounded-xl">
                <view v-for="n in 3" :key="n" class="text-center px-1">
                    <view class="skeleton-box w-8 h-3 mx-auto mb-1"></view>
                    <view class="skeleton-box w-12 h-3 mx-auto"></view>
                </view>
            </view>
            <view class="text-center space-y-3">
                <view class="skeleton-box w-48 h-3 mx-auto"></view>
                <view class="skeleton-box w-40 h-6 mx-auto"></view>
                <view class="skeleton-box w-56 h-8 mx-auto"></view>
            </view>
        </view>
    </view>
</template>

<style scoped>
.skeleton-box {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: skeleton-loading 1.5s infinite;
    border-radius: 4px;
}

.skeleton-circle {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: skeleton-loading 1.5s infinite;
    border-radius: 50%;
}

@keyframes skeleton-loading {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}

.skeleton-shimmer {
    background: linear-gradient(
        90deg,
        rgba(255, 255, 255, 0) 0%,
        rgba(255, 255, 255, 0.4) 50%,
        rgba(255, 255, 255, 0) 100%
    );
    background-size: 200% 100%;
    animation: shimmer 2s infinite;
}

@keyframes shimmer {
    0% {
        background-position: -200% 0;
    }
    100% {
        background-position: 200% 0;
    }
}

.skeleton-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.skeleton-wave {
    background: linear-gradient(90deg, #f0f0f0 0%, #e0e0e0 20%, #d0d0d0 40%, #e0e0e0 60%, #f0f0f0 80%);
    background-size: 200% 100%;
    animation: wave 1.8s ease-in-out infinite;
}

@keyframes wave {
    0% {
        background-position: -200% 0;
    }
    100% {
        background-position: 200% 0;
    }
}

.skeleton-individual {
    background: linear-gradient(90deg, #eff6ff 25%, #dbeafe 50%, #eff6ff 75%);
    background-size: 200% 100%;
    animation: skeleton-loading 1.5s infinite;
}

.skeleton-local {
    background: linear-gradient(90deg, #fff7ed 25%, #fed7aa 50%, #fff7ed 75%);
    background-size: 200% 100%;
    animation: skeleton-loading 1.5s infinite;
}

@media (prefers-color-scheme: dark) {
    .skeleton-box {
        background: linear-gradient(90deg, #374151 25%, #4b5563 50%, #374151 75%);
        background-size: 200% 100%;
    }

    .skeleton-circle {
        background: linear-gradient(90deg, #374151 25%, #4b5563 50%, #374151 75%);
        background-size: 200% 100%;
    }
}
</style>
