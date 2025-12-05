<template>
    <ElDrawer v-model="show" body-class="bg-app-bg-2 !p-0" size="450px" :with-header="false">
        <div class="h-full flex flex-col">
            <div class="absolute w-6 h-6 top-6 right-6" @click="close">
                <close-btn />
            </div>
            <div class="flex-shrink-0 h-[80px] flex items-center px-4 text-white text-lg font-bold">高级设置</div>
            <div class="mx-4 border-b border-app-border-1 pb-4">
                <div class="flex justify-between items-center">
                    <div class="text-xs text-white">AI智能剪辑</div>
                    <ElSwitch
                        v-model="formData.automatic_clip"
                        style="--el-switch-off-color: #333333"
                        :active-value="1"
                        :inactive-value="0" />
                </div>
                <template v-if="formData.automatic_clip == 1">
                    <div class="mt-3">
                        <div class="text-xs text-white mb-3">剪辑风格选择：</div>
                        <div>
                            <ElSelect
                                v-model="clipStyle"
                                class="!h-11"
                                popper-class="dark-select-popper"
                                multiple
                                collapse-tags
                                collapse-tags-tooltip
                                placeholder="请选择剪辑风格"
                                :show-arrow="false"
                                @change="handleChangeClipStyle">
                                <ElOption v-for="(label, key) in ClipStyleMap" :key="key" :label="label" :value="key">
                                </ElOption>
                            </ElSelect>
                        </div>
                    </div>
                    <div class="mt-3" v-if="false">
                        <div class="text-xs text-white mb-3">背景音乐上传：</div>
                        <ElPopover
                            width="264"
                            popper-class="!rounded-xl !bg-app-bg-2 !border-app-border-2 !p-2 choose-type-popover"
                            :show-arrow="false">
                            <template #reference>
                                <div
                                    class="w-full h-11 rounded-md shadow-[0_0_0_1px_var(--app-border-color-1)] flex items-center justify-center gap-x-2 text-white hover:bg-[#ffffff0d] cursor-pointer">
                                    <Icon name="local-icon-upload3"></Icon>
                                    <div class="text-xs">添加音乐</div>
                                </div>
                            </template>
                            <div class="flex flex-col gap-y-2">
                                <div class="type-menu-item" @click="handleSelectAudio">
                                    <span class="flex items-center justify-center rounded p-1 bg-[#ffffff0d]">
                                        <Icon name="local-icon-import" color="#ffffff"></Icon>
                                    </span>
                                    <span class="text-[#ffffffcc]"> 从素材库选择 </span>
                                </div>
                                <upload
                                    class="w-full"
                                    type="audio"
                                    show-progress
                                    :show-file-list="false"
                                    :limit="99 - formData.music.length"
                                    @success="getUploadBgMusic">
                                    <div class="type-menu-item">
                                        <span class="flex items-center justify-center rounded p-1 bg-[#ffffff0d]">
                                            <Icon name="local-icon-upload" color="#ffffff"></Icon>
                                        </span>
                                        <span class="text-[#ffffffcc]"> 从本地上传</span>
                                    </div>
                                </upload>
                            </div>
                        </ElPopover>
                    </div>
                </template>
            </div>
            <div class="grow min-h-0 pb-[100px]" v-if="formData.automatic_clip">
                <ElScrollbar>
                    <div class="flex flex-col gap-y-2 px-4">
                        <div
                            v-for="(item, index) in formData.music"
                            :key="index"
                            class="rounded-md h-11 px-3 border border-app-border-1 bg-app-bg-2 flex items-center justify-between gap-x-2 cursor-pointer hover:bg-[#ffffff0d]">
                            <div class="flex-1 flex items-center gap-x-2">
                                <div class="w-5 h-5 flex items-center justify-center rounded bg-[#ffffff0d]">
                                    <Icon name="local-icon-music" :size="14" color="#ffffff"></Icon>
                                </div>
                                <div class="text-white text-base line-clamp-1">
                                    {{ item.name }}
                                </div>
                            </div>
                            <div class="w-[1px] h-[12px] bg-[#ffffff1a]"></div>
                            <div>
                                <div class="w-4 h-4" @click="handleDeleteMusic(index)">
                                    <close-btn :icon-size="10"></close-btn>
                                </div>
                            </div>
                        </div>
                    </div>
                </ElScrollbar>
            </div>
            <div
                class="absolute bottom-0 left-0 right-0 h-[80px] flex items-center px-4 shadow-[0_-1px_0_0_var(--app-border-color-1)] bg-app-bg-2">
                <ElButton type="primary" class="w-full !h-[50px] !rounded-full" @click="handleConfirm">确定</ElButton>
            </div>
        </div>
    </ElDrawer>
    <audio-material
        v-if="showAudioMaterial"
        ref="audioMaterialRef"
        multiple
        @close="showAudioMaterial = false"
        @confirm="getChooseAudio" />
</template>

<script setup lang="ts">
import AudioMaterial from "@/pages/app/_components/choose-audio.vue";
import { ClipStyleMap } from "@/pages/app/_enums/indexEnum";

type Result = {
    music: Array<{ url: string; name: string }>;
    clip: Array<{ type: number | string }>;
    automatic_clip: number;
};

const emit = defineEmits<{
    (e: "close"): void;
    (e: "success", result: Result): void;
}>();

const formData = reactive<Result>({
    automatic_clip: 0,
    music: [],
    clip: [],
});

const clipStyle = ref<string[]>([]);

const show = defineModel<boolean>("show");

const showAudioMaterial = ref(false);
const audioMaterialRef = shallowRef<InstanceType<typeof AudioMaterial>>();

const handleSelectAudio = async () => {
    showAudioMaterial.value = true;
    await nextTick();
    audioMaterialRef.value.open();
};

const getUploadBgMusic = (result: any) => {
    const { uri, name } = result.data;
    formData.music.push({
        url: uri,
        name,
    });
};

const handleDeleteMusic = (index: number) => {
    formData.music.splice(index, 1);
};

const getChooseAudio = (result: any[]) => {
    formData.music.push(...result.map((item: any) => ({ url: item.url, name: item.name })));
};

const handleChangeClipStyle = (value: string[]) => {
    formData.clip = value.map((item: string) => ({ type: item }));
};

const handleConfirm = () => {
    if (formData.automatic_clip == 1) {
        if (formData.clip.length == 0) {
            feedback.msgWarning("请选择剪辑风格");
            return;
        }
        // if (formData.music.length == 0) {
        //     feedback.msgWarning("请上传背景音乐");
        //     return;
        // }
    }
    close();
    emit("success", formData);
};

const open = () => {
    show.value = true;
};

const close = () => {
    show.value = false;
    emit("close");
};

defineExpose({
    open,
    setFormData: (data) => {
        data.clip = isArray(data.clip) ? data.clip : JSON.parse(data.clip);
        data.music = isArray(data.music) ? data.music : JSON.parse(data.music);
        setFormData(data, formData);
        if (data.clip && data.clip.length > 0) {
            clipStyle.value = data.clip.map((item: any) => item.type);
        }
    },
});
</script>

<style scoped></style>
