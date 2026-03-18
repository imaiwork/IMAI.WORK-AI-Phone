<template>
    <ElPopover
        trigger="click"
        width="280px"
        popper-class="!p-0 !rounded-[24px] !border-none !shadow-[0_20px_50px_rgba(0,0,0,0.12)] overflow-hidden"
        :show-arrow="false"
        placement="bottom-end"
        :offset="12">
        <template #reference>
            <div class="avatar-wrapper group">
                <div class="avatar-ring border-primary">
                    <img
                        :src="userInfo.avatar"
                        class="w-full h-full rounded-full object-cover shadow-inner transition-transform duration-500 group-hover:scale-110" />
                </div>
            </div>
        </template>

        <div class="rounded-[24px] bg-white select-none">
            <div class="p-6 bg-gradient-to-br from-[#f8fafc]/80 to-white border-b border-[#e2e8f0]/60 relative">
                <div class="flex items-center gap-4 mb-4">
                    <div class="relative shrink-0">
                        <img
                            :src="userInfo.avatar"
                            class="w-14 h-14 rounded-[18px] object-cover shadow-md border-2 border-white" />
                        <div
                            class="absolute -bottom-1 -right-1 w-4 h-4 bg-emerald-500 border-2 border-white rounded-full shadow-sm"></div>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="font-[1000] text-slate-900 text-base leading-tight truncate">{{
                            userInfo.nickname
                        }}</span>
                        <div class="flex items-center gap-1.5 mt-2">
                            <span class="text-[11px] text-slate-400 font-bold tracking-wider truncate"
                                >SN: {{ userInfo.sn }}</span
                            >
                        </div>
                    </div>
                </div>

                <div
                    class="flex items-center justify-between px-3 py-2.5 rounded-xl bg-white border border-slate-100 hover:border-[#0065FB]/30 transition-all cursor-pointer group"
                    @click="copy(userInfo.mobile)">
                    <div class="flex items-center gap-2.5">
                        <svg
                            class="w-3.5 h-3.5 text-slate-400 group-hover:text-primary transition-colors"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            stroke-linejoin="round">
                            <path
                                d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                        </svg>
                        <span
                            class="text-xs font-bold text-slate-600 group-hover:item:text-primary transition-colors"
                            >{{ userInfo.mobile }}</span
                        >
                    </div>
                    <svg
                        class="w-3.5 h-3.5 text-slate-300 group-hover:text-primary transition-all"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.5"
                        stroke-linecap="round"
                        stroke-linejoin="round">
                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                    </svg>
                </div>
            </div>

            <div class="p-3 space-y-1">
                <router-link to="/creation" class="menu-item group">
                    <div class="icon-box bg-blue-50 group-hover:bg-primary">
                        <svg
                            class="w-4 h-4 text-primary group-hover:text-white transition-colors"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </div>
                    <span class="label">创作记录</span>
                    <svg
                        class="w-3.5 h-3.5 ml-auto text-slate-300 group-hover:text-primary group-hover:translate-x-0.5 transition-all"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="3"
                        stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="M9 18l6-6-6-6"></path>
                    </svg>
                </router-link>

                <router-link to="/agency" class="menu-item group" v-if="userInfo.is_distribution_agent">
                    <div class="icon-box bg-amber-50 group-hover:bg-amber-500">
                        <svg
                            class="w-4 h-4 text-amber-600 group-hover:text-white transition-colors"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round">
                            <circle cx="12" cy="8" r="7"></circle>
                            <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline>
                        </svg>
                    </div>
                    <span class="label">代理中心</span>
                </router-link>

                <div class="menu-item group" @click="openBase">
                    <div class="icon-box bg-slate-100 group-hover:bg-slate-600">
                        <svg
                            class="w-4 h-4 text-slate-500 group-hover:text-white transition-colors"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        </svg>
                    </div>
                    <span class="label">服务协议与政策</span>
                </div>

                <div class="h-[1px] bg-[#e2e8f0]/60 my-2 mx-3"></div>

                <div class="menu-item group logout-item" @click="quit()">
                    <div class="icon-box bg-rose-50 group-hover:bg-rose-500">
                        <svg
                            class="w-4 h-4 text-rose-500 group-hover:text-white transition-colors"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                    </div>
                    <span class="label group-hover:text-rose-600 font-black">退出账号</span>
                </div>
            </div>

            <div class="px-6 py-4 bg-[#f8fafc]/80 text-center border-t border-[#e2e8f0]/50">
                <span class="text-[10px] font-black text-slate-300 uppercase tracking-[0.2em]"
                    >加入时间：{{ dayjs(userInfo.create_time).format("YYYY.MM.DD") }}</span
                >
            </div>
        </div>
        <base-popup v-if="showBasePop" ref="basePopupRef" @close="showBasePop = false" />
    </ElPopover>
</template>

<script setup lang="ts">
import { useUserStore } from "@/stores/user";
import dayjs from "dayjs";
import { toRefs, ref, shallowRef, nextTick } from "vue";

const userStore = useUserStore();
const { userInfo } = toRefs(userStore);

const showBasePop = ref(false);
const basePopupRef = shallowRef();

const openBase = async () => {
    showBasePop.value = true;
    await nextTick();
    basePopupRef.value?.open();
};

const quit = async () => {
    useNuxtApp().$confirm({
        title: "确定退出登录吗？",
        message: "退出登录后，下次使用需要重新身份验证。",
        onConfirm: () => {
            userStore.logout();
            window.location.reload();
        },
    });
};

const { copy } = useCopy();
</script>

<style scoped lang="scss">
/* 头像外圈动画 */
.avatar-wrapper {
    @apply w-10 h-10 p-[2px] transition-transform active:scale-90 cursor-pointer;
    .avatar-ring {
        @apply w-full h-full rounded-full flex items-center justify-center border-2 p-[2px] transition-all duration-500 group-hover:border-primary group-hover:rotate-[360deg];
    }
}

/* 菜单项通用样式 */
.menu-item {
    @apply flex items-center gap-3.5 px-3 py-2.5 rounded-[18px] cursor-pointer transition-all duration-300;

    &:hover {
        @apply bg-[#F1F6FF] translate-x-1;
    }

    .icon-box {
        @apply w-9 h-9 rounded-[14px] flex items-center justify-center transition-all duration-300;
    }

    .label {
        @apply text-[14px] font-[600] text-slate-600 transition-colors;
    }

    .badge {
        @apply ml-auto px-2 py-0.5 rounded-full bg-amber-100 text-[9px] font-black text-amber-600 uppercase tracking-tighter transition-all;
    }
}

/* 退出登录特殊样式 */
.logout-item:hover {
    @apply bg-rose-50;
    .label {
        @apply text-rose-500;
    }
}

/* 统一重置链接样式 */
a {
    text-decoration: none;
    color: inherit;
    display: block;
}
</style>
