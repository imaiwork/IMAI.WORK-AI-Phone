<template>
    <div v-if="isShowSystemConfig && !loading">
        <Transition
            enter-active-class="transition-all duration-500 ease-out"
            leave-active-class="transition-all duration-300 ease-in"
            enter-from-class="translate-y-4 opacity-0 scale-95"
            enter-to-class="translate-y-0 opacity-100 scale-100"
            leave-from-class="translate-y-0 opacity-100 scale-100"
            leave-to-class="translate-y-4 opacity-0 scale-95">
            <div
                v-if="isFloating"
                @click="handleFloatingClick"
                @mouseenter="handleMouseEnter"
                @mouseleave="handleMouseLeave"
                class="fixed bottom-6 right-6 z-[99999] cursor-pointer transition-all duration-500 ease-out transform hover:scale-105"
                :class="[isHovered ? 'w-72 h-14' : 'w-14 h-14']">
                <div
                    class="relative w-full h-full bg-gradient-to-br from-[#f97316] to-[#ef4444] text-white shadow-2xl transition-all duration-500 ease-out overflow-hidden"
                    :class="[isHovered ? 'rounded-full' : 'rounded-full']">
                    <div v-if="!isHovered" class="absolute inset-0">
                        <div
                            class="absolute inset-0 rounded-full border-2 border-[#f97316] opacity-60 animate-ripple-1"></div>
                        <div
                            class="absolute inset-0 rounded-full border-2 border-[#f97316] opacity-40 animate-ripple-2"></div>
                        <div
                            class="absolute inset-0 rounded-full border-2 border-[#f97316] opacity-20 animate-ripple-3"></div>
                    </div>

                    <div class="relative w-full h-full flex items-center">
                        <div v-if="!isHovered" class="w-full h-full flex items-center justify-center">
                            <div class="relative">
                                <el-icon class="text-2xl animate-bounce">
                                    <Bell />
                                </el-icon>
                                <div
                                    v-if="unconfiguredCount > 0"
                                    class="absolute -top-2 -right-2 w-5 h-5 bg-[#fbbf24] text-white rounded-full flex items-center justify-center text-xs font-bold animate-pulse">
                                    {{ unconfiguredCount }}
                                </div>
                            </div>
                        </div>

                        <div v-if="isHovered" class="w-full h-full flex items-center px-4 space-x-3">
                            <div class="flex-shrink-0">
                                <div class="relative">
                                    <el-icon class="text-lg">
                                        <Bell />
                                    </el-icon>
                                    <div
                                        v-if="unconfiguredCount > 0"
                                        class="absolute -top-1 -right-1 w-3 h-3 bg-[#fbbf24] rounded-full text-white flex items-center justify-center text-[10px] font-medium">
                                        {{ unconfiguredCount }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium truncate">
                                    {{ firstUnconfigured ? firstUnconfigured.name : "系统配置" }}
                                </div>
                                <div class="text-xs opacity-90 truncate">
                                    {{ firstUnconfigured ? "未配置" : "配置完成" }}
                                </div>
                            </div>

                            <div class="flex-shrink-0">
                                <el-icon class="text-sm opacity-70">
                                    <ArrowRight />
                                </el-icon>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <Transition
            enter-active-class="transition-all duration-500"
            leave-active-class="transition-all duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0">
            <div v-if="isModalOpen" class="fixed inset-0 z-[99999] backdrop-blur-sm bg-opacity-50">
                <div @click="handleCloseModal" class="absolute inset-0 bg-black bg-opacity-50"></div>

                <div class="absolute inset-0 flex items-center justify-center p-4">
                    <Transition
                        enter-active-class="transition-all duration-500 transform"
                        leave-active-class="transition-all duration-300 transform"
                        enter-from-class="scale-95 opacity-0"
                        enter-to-class="scale-100 opacity-100"
                        leave-from-class="scale-100 opacity-100"
                        leave-to-class="scale-95 opacity-0">
                        <div
                            v-if="isModalOpen"
                            class="bg-white rounded-lg shadow-xl max-w-lg w-full max-h-[90vh] overflow-hidden">
                            <div class="flex items-center justify-between px-6 py-4 border-b border-[#e5e7eb]">
                                <div class="flex items-center space-x-3">
                                    <el-icon class="text-xl text-[#6b7280]">
                                        <Setting />
                                    </el-icon>
                                    <h3 class="text-lg font-medium text-[#111827]">系统配置检查</h3>
                                </div>
                                <button
                                    @click="handleCloseModal"
                                    class="p-1 hover:bg-[#f3f4f6] rounded-md transition-colors">
                                    <el-icon class="text-[#6b7280] text-lg">
                                        <Close />
                                    </el-icon>
                                </button>
                            </div>

                            <div class="px-6 py-4 bg-[#fef3c7] border-b border-[#e5e7eb]">
                                <div class="flex items-start space-x-3">
                                    <el-icon class="text-[#d97706] text-lg mt-0.5 flex-shrink-0">
                                        <WarningFilled />
                                    </el-icon>
                                    <div>
                                        <p class="text-sm text-[#92400e] font-medium">
                                            检测到 {{ unconfiguredCount }} 项配置未完成
                                        </p>
                                        <p class="text-xs text-[#a16207] mt-1">
                                            为确保系统正常运行，请及时完成相关配置
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="max-h-96 overflow-y-auto">
                                <div class="px-6 py-4">
                                    <div class="space-y-3">
                                        <div
                                            v-for="(item, index) in systemConfig"
                                            :key="item.key"
                                            class="flex items-center justify-between py-3 border-b border-[#f3f4f6] last:border-b-0">
                                            <div class="flex items-center space-x-3 flex-1">
                                                <div class="flex-shrink-0">
                                                    <div
                                                        v-if="item.status === 1"
                                                        class="w-5 h-5 bg-[#10b981] rounded-full flex items-center justify-center">
                                                        <el-icon class="text-white text-xs">
                                                            <Check />
                                                        </el-icon>
                                                    </div>
                                                    <div
                                                        v-else
                                                        class="w-5 h-5 bg-[#ef4444] rounded-full flex items-center justify-center">
                                                        <el-icon class="text-white text-xs">
                                                            <Close />
                                                        </el-icon>
                                                    </div>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-medium text-[#111827] truncate">
                                                        {{ item.name }}
                                                    </p>
                                                    <p
                                                        class="text-xs mt-0.5"
                                                        :class="[
                                                            item.status === 1 ? 'text-[#059669]' : 'text-[#dc2626]',
                                                        ]">
                                                        {{ item.status === 1 ? "已配置" : "未配置" }}
                                                    </p>
                                                </div>
                                            </div>
                                            <el-button type="primary" @click="handleGoConfig(item.routeAuth)"
                                                >前往配置</el-button
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup lang="ts">
import { checkSystemConfig } from "@/api/app";
import { getRoutePath } from "@/router";

const emit = defineEmits<{
    close: [];
}>();

const router = useRouter();

const loading = ref(true);

// 状态管理
const isShowSystemConfig = ref(false);
const isFloating = ref(true);
const isModalOpen = ref(false);
const isHovered = ref(false);
const systemConfig = ref<any>([
    {
        key: "mnp_setting",
        name: "小程序配置",
        status: 0,
        routeAuth: "channel.mnp_settings/setting",
    },
    {
        key: "oa_setting",
        name: "公众号配置",
        status: 0,
        routeAuth: "channel.official_account_setting/getConfig",
    },
    {
        key: "sms_setting",
        name: "短信验证码配置",
        status: 0,
        routeAuth: "notice.sms_config/getConfig",
    },
    {
        key: "api_key",
        name: "商用配置",
        status: 0,
        routeAuth: "setting.setting/activate",
    },
    {
        key: "pay_setting",
        name: "支付配置",
        status: 0,
        routeAuth: "setting.pay.pay_config/lists",
    },
    {
        key: "oss_setting",
        name: "存储配置",
        status: 0,
        routeAuth: "setting.storage/lists",
    },
]);

// 计算属性
const firstUnconfigured = computed(() => systemConfig.value.find((item: any) => item.status === 0));

const unconfiguredCount = computed(() => systemConfig.value.filter((item: any) => item.status === 0).length);

// 方法
const handleFloatingClick = () => {
    isFloating.value = false;
    setTimeout(() => {
        isModalOpen.value = true;
    }, 300);
};

const handleCloseModal = () => {
    isModalOpen.value = false;
    getSystemConfig();
    emit("close");
    setTimeout(() => {
        isFloating.value = true;
    }, 300);
};

const handleGoConfig = (routeAuth: string) => {
    const routePath = getRoutePath(routeAuth);
    router.push(routePath);
    handleCloseModal();
};

const handleMouseEnter = () => {
    isHovered.value = true;
};

const handleMouseLeave = () => {
    isHovered.value = false;
};

const show = () => {
    isShowSystemConfig.value = true;
};

const hide = () => {
    isShowSystemConfig.value = false;
};

const getSystemConfig = async () => {
    try {
        const res = await checkSystemConfig();
        systemConfig.value.forEach((item: any) => {
            item.status = res[item.key] || 0;
        });
        isShowSystemConfig.value = systemConfig.value.some((item: any) => item.status === 0);
    } finally {
        loading.value = false;
    }
};

// 暴露给父组件的方法
defineExpose({
    show,
    hide,
    systemConfig: computed(() => systemConfig.value),
    unconfiguredCount,
});

// 初始化
onMounted(() => {
    getSystemConfig();
});
</script>

<style scoped>
.animate-bounce {
    animation: bounce 1s infinite;
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

.animate-ripple-1 {
    animation: ripple 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    animation-delay: 0s;
}

.animate-ripple-2 {
    animation: ripple 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    animation-delay: 0.5s;
}

.animate-ripple-3 {
    animation: ripple 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    animation-delay: 1s;
}

@keyframes bounce {
    0%,
    100% {
        transform: translateY(-25%);
        animation-timing-function: cubic-bezier(0.8, 0, 1, 1);
    }
    50% {
        transform: none;
        animation-timing-function: cubic-bezier(0, 0, 0.2, 1);
    }
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

@keyframes ripple {
    0% {
        transform: scale(1);
        opacity: 0.6;
    }
    50% {
        opacity: 0.3;
    }
    100% {
        transform: scale(2.5);
        opacity: 0;
    }
}
</style>
