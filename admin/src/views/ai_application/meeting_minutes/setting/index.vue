<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <div class="text-xl font-medium mb-[20px]">基础配置</div>
            <el-form :model="formData" label-width="120px">
                <el-form-item label="发言者头像" prop="avatars">
                    <material-picker v-model="formData.avatars" :limit="10" />
                </el-form-item>
                <el-form-item label="会议语言配置">
                    <div class="flex flex-col gap-2">
                        <div v-for="item in formData.language" class="flex gap-2">
                            <el-input v-model="item.name" class="w-[300px]" />
                            <el-switch v-model="item.status" active-value="1" inactive-value="0" />
                        </div>
                    </div>
                </el-form-item>
                <el-form-item label="翻译语言配置">
                    <div class="flex flex-col gap-2">
                        <div v-for="item in formData.translation" class="flex gap-2">
                            <el-input v-model="item.name" class="w-[300px]" />
                            <el-switch v-model="item.status" active-value="1" inactive-value="0" />
                        </div>
                    </div>
                </el-form-item>
            </el-form>
        </el-card>
    </div>
    <footer-btns>
        <el-button
            v-perms="['ai_application.meeting_minutes/setConfig']"
            type="primary"
            :loading="isLock"
            @click="lockSave"
            >保存</el-button
        >
    </footer-btns>
</template>

<script setup lang="ts">
import { saveConfig } from "@/api/app";
import { useLockFn } from "@/hooks/useLockFn";
import useAppStore from "@/stores/modules/app";

const appStore = useAppStore();

const formData = computed(() => {
    const { meeting_config } = appStore.config;
    return meeting_config;
});

const handleSave = async () => {
    await saveConfig({
        type: "meeting",
        name: "config",
        data: formData.value,
    });
    appStore.getConfig();
};

const { lockFn: lockSave, isLock } = useLockFn(handleSave);
</script>

<style scoped></style>
