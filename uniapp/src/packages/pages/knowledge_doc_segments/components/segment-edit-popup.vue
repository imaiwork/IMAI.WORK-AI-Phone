<template>
    <popup-bottom v-model="show" :clearable="false" height="86%" custom-class="bg-white">
        <template #header>
            <view class="px-[36rpx] pt-3 pb-[24rpx] border-[0] border-b border-solid border-[#F0F2F7]">
                <view class="w-[66rpx] h-[8rpx] rounded-full bg-[#E5E7EB] mx-auto mb-[20rpx]"></view>
                <view class="flex items-center gap-x-[20rpx]">
                    <view
                        class="w-[68rpx] h-[68rpx] rounded-[18rpx] bg-[#EBF2FF] flex items-center justify-center flex-shrink-0">
                        <u-icon :name="isEdit ? 'edit-pen' : 'plus'" color="#2563EB" :size="34" />
                    </view>
                    <view class="flex-1 min-w-0">
                        <text class="text-[32rpx] font-extrabold text-[#1E293B]">
                            {{ isEdit ? "编辑" : "新增" }}分段数据
                        </text>
                        <text class="block text-[22rpx] text-[#94A3B8] mt-[4rpx]">
                            手动维护知识库的分段内容及关联多媒体素材
                        </text>
                    </view>
                    <view
                        class="w-[56rpx] h-[56rpx] rounded-full bg-[#F3F4F6] flex items-center justify-center"
                        @click="show = false">
                        <u-icon name="close" color="#666666" :size="20" />
                    </view>
                </view>
            </view>
        </template>
        <template #content>
            <view class="h-full w-full flex flex-col">
                <view class="grow min-h-0">
                    <scroll-view scroll-y class="h-full">
                        <view class="w-full px-[36rpx] py-[28rpx] flex flex-col gap-y-[28rpx]">
                            <!-- 文档内容 -->
                            <view class="w-full">
                                <view class="label-box">
                                    <view class="dot bg-[#2563EB]"></view>
                                    <text class="label-title">文档内容</text>
                                    <text class="label-sub">会遇到的提问</text>
                                </view>
                                <view class="seg-ta">
                                    <textarea v-model="form.question" placeholder="请输入文档核心内容或模拟提问..." />
                                </view>
                            </view>

                            <!-- 说明内容 -->
                            <view>
                                <view class="label-box">
                                    <view class="dot bg-[#10B981]"></view>
                                    <text class="label-title">说明内容</text>
                                    <text class="label-sub">如何回复 / 详情</text>
                                </view>
                                <view class="seg-ta">
                                    <textarea
                                        v-model="form.answer"
                                        class="w-full"
                                        placeholder="请输入对应的回答或补充说明信息..." />
                                </view>
                            </view>

                            <!-- 附加素材 -->
                            <view class="bg-[#F8FAFC] rounded-[24rpx] p-[28rpx] flex flex-col gap-y-[28rpx]">
                                <!-- 附加图片 -->
                                <view>
                                    <view class="flex items-center justify-between mb-[16rpx]">
                                        <text class="text-[26rpx] font-bold text-[#1E293B]">附加图片</text>
                                        <text class="text-[22rpx] text-[#2563EB]">{{ form.images.length }}/9</text>
                                    </view>
                                    <view class="flex flex-wrap gap-[16rpx]">
                                        <view v-for="(img, index) in form.images" :key="index" class="img-card">
                                            <image
                                                :src="img.url"
                                                class="w-full h-full rounded-[14rpx]"
                                                mode="aspectFill" />
                                            <view class="img-del" @click="form.images.splice(index, 1)">
                                                <u-icon name="close" color="#ffffff" :size="18" />
                                            </view>
                                        </view>
                                        <view
                                            v-if="form.images.length < 9"
                                            class="img-add"
                                            @click="chooseImage('image')">
                                            <u-icon name="plus" color="#94A3B8" :size="36" />
                                        </view>
                                    </view>
                                </view>

                                <!-- 关联视频 -->
                                <view>
                                    <view class="flex items-center justify-between mb-[16rpx]">
                                        <text class="text-[26rpx] font-bold text-[#1E293B]">关联视频</text>
                                        <text class="text-[22rpx] text-[#94A3B8]">MP4, 小于20MB</text>
                                    </view>
                                    <view v-if="!form.video.length" class="video-add" @click="chooseVideo('video')">
                                        <u-icon name="play-circle" color="#94A3B8" :size="40" />
                                        <text class="text-[22rpx] text-[#94A3B8] mt-[8rpx]">上传视频</text>
                                    </view>
                                    <view v-else class="video-preview">
                                        <video
                                            :src="form.video[0].url"
                                            class="w-full h-full"
                                            object-fit="cover"
                                            :controls="true"
                                            :show-center-play-btn="true" />
                                        <view class="img-del" @click="form.video = []">
                                            <u-icon name="close" color="#ffffff" :size="18" />
                                        </view>
                                    </view>
                                </view>

                                <!-- 相关附件 -->
                                <view>
                                    <view class="flex items-center justify-between mb-[16rpx]">
                                        <text class="text-[26rpx] font-bold text-[#1E293B]">相关附件</text>
                                        <text class="text-[22rpx] text-[#94A3B8]">PDF, Docx, MD...</text>
                                    </view>
                                    <view
                                        v-if="form.files.length < 1"
                                        class="file-add"
                                        @click="chooseFile('file')">
                                        <u-icon name="attach" color="#64748B" :size="28" />
                                        <text class="text-[24rpx] font-bold text-[#64748B]">选取附件文件</text>
                                    </view>
                                    <view class="flex flex-col gap-y-[12rpx] mt-[12rpx]">
                                        <view v-for="(file, index) in form.files" :key="index" class="file-item">
                                            <u-icon name="file-text" color="#2563EB" :size="28" />
                                            <text class="file-name">{{ file.name }}</text>
                                            <view
                                                class="w-[40rpx] h-[40rpx] flex items-center justify-center"
                                                @click="form.files.splice(index, 1)">
                                                <u-icon name="close" color="#94A3B8" :size="20" />
                                            </view>
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </scroll-view>
                </view>
                <!-- 底部 -->
                <view
                    class="flex gap-x-[20rpx] px-[32rpx] pt-[16rpx] pb-[calc(20rpx+env(safe-area-inset-bottom))] border-0 border-t border-solid border-[#F0F2F7]">
                    <view class="footer-btn footer-btn--cancel" @click="show = false">取消</view>
                    <view class="footer-btn footer-btn--save" @click="lockFn">提交保存</view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script lang="ts" setup>
import {
    vectorKnowledgeBaseChunkAdd,
    vectorKnowledgeBaseChunkEdit,
    vectorKnowledgeBaseChunkDetail,
} from "@/api/knowledge_base";
import { setFormData } from "@/utils/util";
import { useLockFn } from "@/hooks/useLockFn";
import useUpload from "@/hooks/useUpload";

const props = defineProps<{
    modelValue: boolean;
    kbId: string | number;
    fdId: string | number;
    editUuid?: string;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", v: boolean): void;
    (e: "success"): void;
}>();

const show = computed({
    get: () => props.modelValue,
    set: (v: boolean) => emit("update:modelValue", v),
});

const isEdit = computed(() => !!props.editUuid);

const form = reactive<any>({
    kb_id: "",
    fd_id: "",
    uuid: "",
    question: "",
    answer: "",
    images: [] as any[],
    video: [] as any[],
    files: [] as any[],
});

const resetForm = () => {
    Object.assign(form, {
        kb_id: props.kbId,
        fd_id: props.fdId,
        uuid: "",
        question: "",
        answer: "",
        images: [],
        video: [],
        files: [],
    });
};

watch(
    () => props.modelValue,
    async (v) => {
        if (!v) return;
        resetForm();
        if (props.editUuid) {
            uni.showLoading({ title: "加载中...", mask: true });
            try {
                const res: any = await vectorKnowledgeBaseChunkDetail({ uuid: props.editUuid });
                setFormData(res, form);
                form.images = res.images || [];
                form.video = res.video || [];
                form.files = res.files || [];
            } catch (error) {
                uni.$u.toast("获取分段详情失败");
            } finally {
                uni.hideLoading();
            }
        }
    },
);

const ATTACH_FILE_ACCEPT = ["pdf", "docx", "doc", "txt", "md"];

const { uploadAndProcessFiles: chooseImage } = useUpload({
    count: 9,
    imageResolution: [99999, 99999],
    onSuccess: (materials: any[]) => {
        materials.forEach((m) => {
            if (m.url && form.images.length < 9) form.images.push({ name: m.name, url: m.url });
        });
    },
});

const { uploadAndProcessFiles: chooseVideo } = useUpload({
    count: 1,
    videoAccept: ["mp4"],
    videoSize: 20,
    // 与 PC 一致：不限制时长；空数组可跳过 useUpload 内时长校验
    videoDuration: [] as unknown as [number, number],
    onSuccess: (materials: any[]) => {
        const m = materials[0];
        if (m?.url) form.video = [{ name: m.name, url: m.url }];
    },
});

const { uploadAndProcessFiles: chooseFile } = useUpload({
    count: 1,
    fileAccept: ATTACH_FILE_ACCEPT,
    fileSize: 50,
    onSuccess: (materials: any[]) => {
        const m = materials[0];
        if (m?.url) form.files = [{ name: m.name, url: m.url }];
    },
});

const handleSubmit = async () => {
    if (!form.question.trim()) {
        uni.$u.toast("请输入文档内容");
        return;
    }
    uni.showLoading({ title: "保存中...", mask: true });
    try {
        const payload = { ...form };
        isEdit.value ? await vectorKnowledgeBaseChunkEdit(payload) : await vectorKnowledgeBaseChunkAdd(payload);
        uni.hideLoading();
        uni.showToast({ title: "保存成功", icon: "none" });
        emit("success");
        emit("update:modelValue", false);
    } catch (error: any) {
        uni.hideLoading();
        uni.$u.toast(typeof error === "string" ? error : "保存失败");
    }
};

const { lockFn } = useLockFn(handleSubmit);
</script>

<style lang="scss" scoped>
.label-box {
    @apply flex items-center gap-x-[10rpx] mb-[14rpx];
}
.dot {
    @apply w-[12rpx] h-[12rpx] rounded-full flex-shrink-0;
}
.label-title {
    @apply text-[28rpx] font-bold text-[#1E293B];
}
.label-sub {
    @apply text-[20rpx] text-[#94A3B8];
}
.seg-ta {
    @apply w-full bg-[#F7F9FC] rounded-[20rpx] px-[24rpx] py-[20rpx] text-[28rpx] text-[#1D2129] leading-relaxed min-h-[200rpx] border border-solid border-[#E5EAF3];
}
.img-card {
    @apply w-[150rpx] h-[150rpx] rounded-[14rpx] relative overflow-hidden;
}
.img-del {
    @apply absolute top-[6rpx] right-[6rpx] w-[36rpx] h-[36rpx] rounded-full bg-[#0009] flex items-center justify-center;
}
.img-add {
    @apply w-[150rpx] h-[150rpx] rounded-[14rpx] bg-white border-[2rpx] border-dashed border-[#CBD5E1] flex items-center justify-center active:opacity-70;
}
.video-add {
    @apply h-[220rpx] w-full rounded-[20rpx] bg-white border-[2rpx] border-dashed border-[#CBD5E1] flex flex-col items-center justify-center active:opacity-70;
}
.video-preview {
    @apply h-[280rpx] w-full rounded-[20rpx] overflow-hidden relative bg-black border border-solid border-[#E5EAF3];
}
.file-add {
    @apply h-[80rpx] px-[24rpx] rounded-[16rpx] bg-white border border-solid border-[#E5EAF3] flex items-center justify-center gap-x-[12rpx] active:opacity-70;
}
.file-item {
    @apply flex items-center gap-x-[12rpx] px-[20rpx] py-[16rpx] bg-white border border-solid border-[#F1F5F9] rounded-[16rpx];
}
.file-name {
    @apply flex-1 min-w-0 text-[24rpx] text-[#1E293B] truncate;
}
.footer-btn {
    @apply flex-1 h-[88rpx] rounded-[20rpx] flex items-center justify-center text-[28rpx] font-bold active:opacity-85;
}
.footer-btn--cancel {
    @apply bg-[#F1F5F9] text-[#475569];
}
.footer-btn--save {
    @apply text-white;
    background: linear-gradient(135deg, #3d82f7, #2563eb);
}
</style>
