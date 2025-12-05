<template>
    <ElPopover
        placement="bottom"
        popper-class="!rounded-[20px] !p-6"
        :width="320"
        :show-arrow="false"
        :popper-options="{
            modifiers: [
                {
                    name: 'offset',
                    options: {
                        offset: [-92, 10],
                    },
                },
            ],
        }">
        <template #reference>
            <div class="free-experience-btn" @click="toggleShowLogin(true)">
                <Icon name="local-icon-tokens" color="#D6A670" :size="20"></Icon>
                <span class="font-bold text-white text">免费体验</span>
            </div>
        </template>
        <div>
            <div class="flex flex-col gap-y-4">
                <div class="flex gap-x-2">
                    <div
                        class="w-8 h-8 flex items-center justify-center rounded-md bg-[rgba(0,101,251,0.05)] flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path
                                opacity="0.5"
                                d="M11.3334 14H4.66669C3.56669 14 2.66669 13.1 2.66669 12V4C2.66669 2.9 3.56669 2 4.66669 2H11.3334C12.4334 2 13.3334 2.9 13.3334 4V12C13.3334 13.1 12.4334 14 11.3334 14ZM4.66669 3.33333C4.30002 3.33333 4.00002 3.63333 4.00002 4V12C4.00002 12.3667 4.30002 12.6667 4.66669 12.6667H11.3334C11.7 12.6667 12 12.3667 12 12V4C12 3.63333 11.7 3.33333 11.3334 3.33333H4.66669Z"
                                fill="#0065FB" />
                            <path d="M10.6666 10H5.33331V11.3333H10.6666V10Z" fill="#0065FB" />
                            <path
                                d="M7.33334 8.66426C7.16001 8.66426 6.99334 8.59759 6.86001 8.47092L5.52667 7.13759L6.46667 6.19759L7.32667 7.05759L9.52001 4.86426L10.46 5.80426L7.79334 8.47092C7.66001 8.60426 7.49334 8.66426 7.32001 8.66426H7.33334Z"
                                fill="#0065FB" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[#000000] font-bold">高级功能</div>
                        <div class="text-[rgba(0,0,0,0.5)] text-xs mt-1">
                            AI 数字人、AI 面试、ChatGPT、AI 会议纪要等多种 AI 功能，让繁琐工作 AI 来完成。
                        </div>
                    </div>
                </div>
                <div class="flex gap-x-2">
                    <div
                        class="w-8 h-8 flex items-center justify-center rounded-md bg-[rgba(0,101,251,0.05)] flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path
                                d="M8.00002 5.99967C9.28868 5.99967 10.3334 4.95501 10.3334 3.66634C10.3334 2.37768 9.28868 1.33301 8.00002 1.33301C6.71136 1.33301 5.66669 2.37768 5.66669 3.66634C5.66669 4.95501 6.71136 5.99967 8.00002 5.99967Z"
                                fill="#0065FB" />
                            <path
                                d="M3.66665 13.9997C4.95531 13.9997 5.99998 12.955 5.99998 11.6663C5.99998 10.3777 4.95531 9.33301 3.66665 9.33301C2.37798 9.33301 1.33331 10.3777 1.33331 11.6663C1.33331 12.955 2.37798 13.9997 3.66665 13.9997Z"
                                fill="#0065FB" />
                            <path
                                d="M12.3333 13.9997C13.622 13.9997 14.6667 12.955 14.6667 11.6663C14.6667 10.3777 13.622 9.33301 12.3333 9.33301C11.0447 9.33301 10 10.3777 10 11.6663C10 12.955 11.0447 13.9997 12.3333 13.9997Z"
                                fill="#0065FB" />
                            <path
                                opacity="0.5"
                                d="M12.3201 8.77361L13.6534 8.70694C13.5668 6.92694 12.6268 5.26694 11.1401 4.28027L10.4001 5.38694C11.5401 6.14694 12.2601 7.41361 12.3268 8.77361H12.3201Z"
                                fill="#0065FB" />
                            <path
                                opacity="0.5"
                                d="M5.60001 5.38694L4.86001 4.28027C3.37335 5.27361 2.43335 6.92694 2.34668 8.70694L3.68001 8.77361C3.74668 7.41361 4.46668 6.14694 5.60668 5.38694H5.60001Z"
                                fill="#0065FB" />
                            <path
                                opacity="0.5"
                                d="M6.24653 12.9658L5.7132 14.1858C6.43986 14.5058 7.2132 14.6658 7.99986 14.6658C8.78653 14.6658 9.55986 14.5058 10.2865 14.1858L9.7532 12.9658C8.6332 13.4592 7.36653 13.4592 6.24653 12.9658Z"
                                fill="#0065FB" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[#000000] font-bold">智能体</div>
                        <div class="text-[rgba(0,0,0,0.5)] text-xs mt-1">
                            强大智能体，免写提示词，高效可以，任何内容创作直接使用。
                        </div>
                    </div>
                </div>
                <div class="flex gap-x-2">
                    <div
                        class="w-8 h-8 flex items-center justify-center rounded-md bg-[rgba(0,101,251,0.05)] flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path
                                d="M2.36667 5.2622L7.7 7.92887C7.79333 7.97553 7.89333 8.0022 8 8.0022C8.10667 8.0022 8.20667 7.97553 8.3 7.92887L13.6333 5.2622C13.86 5.14887 14 4.91553 14 4.66887C14 4.4222 13.86 4.1822 13.6333 4.07553L8.3 1.40887C8.11333 1.31553 7.89333 1.31553 7.70667 1.40887L2.36667 4.07553C2.14 4.18887 2 4.4222 2 4.66887C2 4.91553 2.14 5.15553 2.36667 5.2622Z"
                                fill="#0065FB" />
                            <path
                                opacity="0.5"
                                d="M13.0334 7.41062L8.00007 9.93062L2.96674 7.41062C2.64007 7.24395 2.2334 7.37729 2.0734 7.71062C1.9134 8.04395 2.04007 8.43729 2.3734 8.60395L7.70674 11.2706C7.80007 11.3173 7.90007 11.344 8.00674 11.344C8.1134 11.344 8.2134 11.3173 8.30674 11.2706L13.6401 8.60395C13.9667 8.43729 14.1001 8.03729 13.9401 7.71062C13.7801 7.38395 13.3734 7.24395 13.0467 7.41062H13.0334Z"
                                fill="#0065FB" />
                            <path
                                opacity="0.5"
                                d="M13.0334 10.7436L8.00007 13.2636L2.96673 10.7436C2.64007 10.577 2.2334 10.7103 2.0734 11.0436C1.90673 11.3703 2.04007 11.7703 2.3734 11.937L7.70673 14.6036C7.80007 14.6503 7.90007 14.677 8.00673 14.677C8.1134 14.677 8.2134 14.6503 8.30673 14.6036L13.6401 11.937C13.9667 11.7703 14.1001 11.3703 13.9401 11.0436C13.7734 10.717 13.3734 10.5836 13.0467 10.7436H13.0334Z"
                                fill="#0065FB" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[#000000] font-bold">商用保障</div>
                        <div class="text-[rgba(0,0,0,0.5)] text-xs mt-1">
                            精益求精，一切都是为了您可以直接商用，而不是走马观花，让 AI 真正有价值。
                        </div>
                    </div>
                </div>
                <div class="flex gap-x-2">
                    <div
                        class="w-8 h-8 flex items-center justify-center rounded-md bg-[rgba(0,101,251,0.05)] flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path
                                opacity="0.5"
                                d="M12 11.3333H4C2.9 11.3333 2 10.4333 2 9.33333V4C2 2.9 2.9 2 4 2H8V3.33333H4C3.63333 3.33333 3.33333 3.63333 3.33333 4V9.33333C3.33333 9.7 3.63333 10 4 10H12C12.3667 10 12.6667 9.7 12.6667 9.33333V8H14V9.33333C14 10.4333 13.1 11.3333 12 11.3333Z"
                                fill="#0065FB" />
                            <path
                                opacity="0.5"
                                d="M12.6667 14.0003H3.33335C2.96669 14.0003 2.66669 13.7003 2.66669 13.3337C2.66669 12.967 2.96669 12.667 3.33335 12.667H12.6667C13.0334 12.667 13.3334 12.967 13.3334 13.3337C13.3334 13.7003 13.0334 14.0003 12.6667 14.0003Z"
                                fill="#0065FB" />
                            <path
                                d="M12 6.66634C13.4727 6.66634 14.6666 5.47243 14.6666 3.99967C14.6666 2.52692 13.4727 1.33301 12 1.33301C10.5272 1.33301 9.33331 2.52692 9.33331 3.99967C9.33331 5.47243 10.5272 6.66634 12 6.66634Z"
                                fill="#0065FB" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[#000000] font-bold">多端通用</div>
                        <div class="text-[rgba(0,0,0,0.5)] text-xs mt-1">
                            Windows、Mac、小程序全面支持，随时随地享用AI高效工作。
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-[25px]">
                <ElButton
                    type="primary"
                    class="!rounded-full !h-11 w-full !shadow-[0px_6px_12px_0px_rgba(0,101,251,0.20)]"
                    @click="toggleShowLogin(true)">
                    立即体验
                </ElButton>
            </div>
        </div>
    </ElPopover>
</template>

<script setup lang="ts">
import { useUserStore } from "@/stores/user";

const userStore = useUserStore();

const { toggleShowLogin } = toRefs(userStore);
</script>

<style lang="scss">
.free-experience-btn {
    @apply px-3 h-10 cursor-pointer flex items-center justify-center rounded-full border gap-x-[6px];
    background: linear-gradient(225deg, #ffe5c0 -174.4%, #1f1f1f 50.08%);
    .text {
        background: linear-gradient(270deg, #fff 0%, #ffe8c7 100%);
        background-clip: text;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
}
</style>
