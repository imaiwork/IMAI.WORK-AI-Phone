<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <div class="text-xl font-medium mb-[20px]">基本配置</div>
            <el-form :model="formData" label-width="140px">
                <el-form-item label="手机演示功能">
                    <div>
                        <el-switch
                            v-model="formData.demo_switch"
                            :active-value="DEMO_SWITCH_ON"
                            :inactive-value="DEMO_SWITCH_OFF" />
                        <div class="form-tips">开启后，前台将展示手机演示相关功能</div>
                    </div>
                </el-form-item>
            </el-form>
        </el-card>
    </div>
    <footer-btns>
        <el-button
            v-perms="['ai_application.device/setConfig']"
            type="primary"
            :loading="isLock"
            @click="lockSubmit">
            保存
        </el-button>
    </footer-btns>
</template>

<script setup lang="ts">
import { saveConfig } from "@/api/app";
import { useLockFn } from "@/hooks/useLockFn";
import useAppStore from "@/stores/modules/app";

const DEMO_SWITCH_OFF = 0;
const DEMO_SWITCH_ON = 1;

const appStore = useAppStore();

const formData = reactive({
    demo_switch: Number(appStore.config?.demo_switch ?? DEMO_SWITCH_OFF)
        ? DEMO_SWITCH_ON
        : DEMO_SWITCH_OFF,
});

const handleSubmit = async () => {
    const demoSwitch = Number(formData.demo_switch) ? DEMO_SWITCH_ON : DEMO_SWITCH_OFF;
    await saveConfig({
        type: "rpa",
        name: "demo_switch",
        data: demoSwitch,
    });
    appStore.config = {
        ...appStore.config,
        demo_switch: demoSwitch,
    };
    formData.demo_switch = demoSwitch;
};

const { lockFn: lockSubmit, isLock } = useLockFn(handleSubmit);
</script>

<style lang="scss" scoped></style>
