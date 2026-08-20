<template>
    <ElPopover
        trigger="click"
        width="280px"
        popper-class="!p-0 !rounded-[24px] !border-none !shadow-[0_20px_50px_rgba(0,0,0,0.12)] overflow-hidden"
        :show-arrow="false"
        placement="bottom-end"
        :offset="12">
        <template #reference>
            <div class="avatar-wrapper group relative">
                <div class="avatar-ring" :class="memberLabel ? 'border-amber-400' : 'border-primary'">
                    <img
                        :src="userInfo.avatar"
                        class="w-full h-full rounded-full object-cover shadow-inner transition-transform duration-500 group-hover:scale-110" />
                </div>
                <div v-if="memberLabel" class="member-crown-badge" :title="memberLabel">
                    <svg viewBox="0 0 24 24" fill="currentColor" class="w-2.5 h-2.5">
                        <path d="M5 16L3 7l5.5 4L12 4l3.5 7L21 7l-2 9H5zm0 2h14v2H5v-2z" />
                    </svg>
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
                    <div class="flex flex-col min-w-0 flex-1 cursor-pointer">
                        <div class="flex items-center gap-2 flex-wrap" @click="openPlan">
                            <span class="font-[1000] text-slate-900 text-base leading-tight truncate">{{
                                userInfo.nickname
                            }}</span>
                            <span class="member-badge" :class="userInfo?.level_id > 0 ? 'is-member' : 'is-free'">
                                <svg
                                    v-if="userInfo?.level_id > 0"
                                    class="w-2.5 h-2.5"
                                    viewBox="0 0 24 24"
                                    fill="currentColor">
                                    <path d="M12 2l3 7h7l-5.5 4 2 8L12 17l-6.5 4 2-8L2 9h7z" />
                                </svg>
                                {{ userInfo?.level_name || "普通用户" }}
                            </span>
                        </div>
                        <div class="flex items-center gap-1.5 mt-2">
                            <span class="text-[11px] text-slate-400 font-bold tracking-wider truncate"
                                >SN: {{ userInfo.sn }}</span
                            >
                            <!-- 增加一个复制按钮，点击后复制 SN -->
                            <span class="cursor-pointer" @click="copy(userInfo.sn)">
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
                            </span>
                        </div>
                        <div
                            v-if="memberLabel && memberExpire"
                            class="mt-1 text-[11px] text-slate-400 font-semibold truncate">
                            会员到期：{{ memberExpire }}
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

                <button class="member-cta mt-3" @click="openPlan">
                    <svg viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                        <path d="M5 16L3 7l5.5 4L12 4l3.5 7L21 7l-2 9H5zm0 2h14v2H5v-2z" />
                    </svg>
                    <span>{{ memberLabel ? "续费会员" : "会员订阅" }}</span>
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="3"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="w-3.5 h-3.5 ml-auto opacity-70">
                        <path d="M9 18l6-6-6-6" />
                    </svg>
                </button>
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

    <!-- 会员订阅信息展示 + 兑换码兑换 -->
    <MemberPlanPopup ref="planRef" />
</template>

<script setup lang="ts">
import { useUserStore } from "@/stores/user";
import { useAppStore } from "@/stores/app";
import MemberPlanPopup from "./member-plan-popup.vue";
import dayjs from "dayjs";
import { toRefs, ref, shallowRef, nextTick, computed } from "vue";

const userStore = useUserStore();
const appStore = useAppStore();
const { userInfo } = toRefs(userStore);

// 会员等级与到期时间，逻辑与 uniapp 端 user 页面保持一致
const memberLabel = computed(() => userInfo.value?.level_name || "");
const memberExpire = computed(() => {
    const info = userInfo.value || {};
    const raw = info.member_expire_time || info.vip_expire_time || info.expire_time || info.valid_time || "";
    if (!raw) return "";
    // 兼容时间戳 / 字符串
    const d = dayjs(typeof raw === "number" ? raw * (raw < 1e12 ? 1000 : 1) : raw);
    return d.isValid() ? d.format("YYYY.MM.DD") : String(raw);
});

const showBasePop = ref(false);
const basePopupRef = shallowRef();
const planRef = ref();
const openPlan = async () => {
    // 打开弹窗时强制刷新；平时读 store（getUser 已拉取）
    await appStore.ensureMemberQuota(true);
    planRef.value?.open(appStore.memberQuota);
};

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
.avatar-wrapper {
    @apply w-10 h-10 p-[2px] transition-transform active:scale-90 cursor-pointer;
    .avatar-ring {
        @apply w-full h-full rounded-full flex items-center justify-center border-2 p-[2px] transition-all duration-500 group-hover:border-primary group-hover:rotate-[360deg];
    }
}

.member-crown-badge {
    @apply absolute -bottom-0.5 -right-0.5 w-4 h-4 rounded-full flex items-center justify-center text-white border-2 border-white;
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
}

.member-chip {
    @apply shrink-0 inline-flex items-center gap-[4px] max-w-[120px] rounded-full px-[8px] py-[3px] text-[10px] font-bold text-white;
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    letter-spacing: 0.02em;
}

/* 会员订阅 CTA */
.member-cta {
    @apply w-full flex items-center gap-2 px-3.5 py-2.5 rounded-xl text-[13px] font-black text-white cursor-pointer transition-all;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 50%, #b45309 100%);
    box-shadow: 0 6px 18px -4px rgba(245, 158, 11, 0.45);
    border: none;
    letter-spacing: 0.02em;

    &:hover {
        @apply -translate-y-0.5;
        box-shadow: 0 10px 24px -6px rgba(245, 158, 11, 0.55);
        filter: brightness(1.05);
    }
    &:active {
        @apply translate-y-0 scale-[0.98];
    }
}

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

.logout-item:hover {
    @apply bg-rose-50;
    .label {
        @apply text-rose-500;
    }
}

.member-badge {
    display: inline-flex;
    align-items: center;
    gap: 2px;
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.02em;
    white-space: nowrap;
    flex-shrink: 0;
    &.is-free {
        background: #f1f5f9;
        color: #64748b;
    }
    &.is-member {
        background: linear-gradient(135deg, #fff7ed, #fce7f3);
        color: #b45309;
        box-shadow: inset 0 0 0 1px rgba(245, 158, 11, 0.3);
        svg {
            color: #f59e0b;
        }
    }
}
a {
    text-decoration: none;
    color: inherit;
    display: block;
}
</style>
