<template>
    <div class="h-full flex flex-col min-w-[1000px] px-4 pb-4">
        <div class="h-[88px] shrink-0 rounded-[20px] bg-white border border-br px-6 flex items-center">
            <ElSkeleton animated class="w-full">
                <template #template>
                    <div class="flex items-center gap-4 w-full">
                        <ElSkeletonItem variant="circle" style="width: 44px; height: 44px" />
                        <div class="w-[160px]">
                            <ElSkeletonItem variant="text" style="width: 120px; height: 16px" />
                            <ElSkeletonItem variant="text" style="width: 80px; height: 12px; margin-top: 8px" />
                        </div>
                        <ElSkeletonItem variant="rect" style="width: 280px; height: 40px; border-radius: 12px" />
                        <ElSkeletonItem variant="rect" style="width: 180px; height: 32px; border-radius: 8px; margin-left: auto" />
                    </div>
                </template>
            </ElSkeleton>
        </div>
        <div class="grow min-h-0 rounded-[20px] bg-white border border-br mt-4 p-8">
            <ElSkeleton animated :rows="8" />
        </div>
    </div>
</template>
