<template>
    <div class="flex items-center">
        <div
            :class="`px-3 h-10 cursor-pointer flex items-center justify-center rounded-full gap-x-[6px] hover:bg-[${getTheme.hoverBgColor}]`"
            :style="{ boxShadow: getTheme.shadow, color: getTheme.iconColor }"
            @click="handleTeamMode">
            <span class="w-5 h-5 flex items-center justify-center rounded-full bg-primary">
                <Icon name="local-icon-user" :size="12" color="#ffffff"></Icon>
            </span>
            <span class="font-bold" :style="{ color: getTheme.textColor }">团队模式</span>
        </div>
        <div class="ml-2">
            <free-experience v-if="!isLogin" />
            <tokens-panel v-else />
        </div>
        <ElDivider direction="vertical" :style="{ borderColor: getTheme.lineColor }" />
        <div>
            <user-panel v-if="isLogin" @recharge="openDataPackage" />
            <div
                v-else
                class="flex items-center bg-primary rounded-full px-4 h-10 text-white cursor-pointer gap-x-[7px] hover:bg-primary-light-3"
                @click="toggleShowLogin()">
                <div class="font-bold">登录</div>
                <ElDivider direction="vertical" class="!border-l-[#ffffff33]" />
                <div class="font-bold">注册</div>
            </div>
        </div>
    </div>
    <data-package ref="dataPackageRef" v-if="showDataPackage"></data-package>
    <ElDialog v-model="showTeamPopup" width="342px" :show-close="false" append-to-body>
        <div>
            <div class="text-[15px] text-[rgba(0,0,0,0.8)] text-center font-bold">团队模式</div>
            <div class="text-[rgba(0,0,0,0.5)] mt-4 text-center text-base">
                团队模式，不再仅仅依赖单一的个人，而是通过团队。
            </div>
            <div class="mt-6 flex items-center">
                <ElButton
                    type="primary"
                    round
                    class="!h-[50px] shadow-[0px_6px_12px_0px_rgba(0,101,251,0.20)] flex-1 !rounded-full"
                    @click="showTeamPopup = false">
                    敬请期待
                </ElButton>
            </div>
        </div>
    </ElDialog>
</template>

<script setup lang="ts">
import { useUserStore } from "@/stores/user";
import DataPackage from "@/components/data-package/index.vue";
import { AppKeyEnum } from "@/enums/appEnums";
import FreeExperience from "./free-experience.vue";
import UserPanel from "./user-panel.vue";
import TokensPanel from "./tokens-panel.vue";
defineProps({
    isWechat: {
        type: Boolean,
        default: false,
    },
});

const route = useRoute();

const userStore = useUserStore();

const { isLogin, toggleShowLogin } = toRefs(userStore);

interface Theme {
    shadow?: string;
    iconColor?: string;
    textColor?: string;
    hoverBgColor?: string;
    lineColor?: string;
}
const getTheme = computed<Theme>(() => {
    const { key, layout } = route.meta;
    if (layout == "wechat") {
        return {
            textColor: "#ffffff",
            shadow: "0 0 0 1px rgba(255,255,255,0.2)",
        };
    }
    switch (key) {
        case AppKeyEnum.DIGITAL_HUMAN:
        case AppKeyEnum.DRAWING:
        case AppKeyEnum.REDBOOK:
        case AppKeyEnum.SPH:
        case AppKeyEnum.MATRIX:
            return {
                shadow: "0 0 0 1px rgba(255,255,255,0.1)",
                iconColor: "rgba(255,255,255,0.8)",
                textColor: "rgba(255,255,255,0.8)",
                hoverBgColor: "rgba(255,255,255,0.1)",
                lineColor: "rgba(255,255,255,0.1)",
            };
        default:
            return {
                shadow: "0 0 0 1px rgba(0,0,0,0.05)",
                iconColor: "#000000",
                textColor: "#000000",
                hoverBgColor: "rgba(0,0,0,0.03)",
                lineColor: "#dcdfe6",
            };
    }
});

const showTeamPopup = ref(false);

const handleTeamMode = () => {
    showTeamPopup.value = true;
};

const showDataPackage = ref<boolean>(false);
const dataPackageRef = ref<InstanceType<typeof DataPackage> | null>(null);

const openDataPackage = async () => {
    showDataPackage.value = true;
    await nextTick();
    dataPackageRef.value?.open();
};
</script>
