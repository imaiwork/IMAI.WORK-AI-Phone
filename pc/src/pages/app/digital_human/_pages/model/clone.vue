<template>
    <div class="h-full min-w-[1000px] px-3 overflow-y-auto custom-scrollbar">
        <div class="mb-4">
            <h1 class="text-[24px] font-[900] text-[#1E293B]">克隆您的数字分身</h1>
            <p class="text-[14px] text-[#64748B] mt-2">通过上传 2 分钟内的视频，即可生成 1:1 复刻的数字形象与声音</p>
        </div>

        <div class="flex flex-col gap-3 lg:flex-row items-start">
            <div class="flex-1 bg-white rounded-[32px] border border-br p-8 space-y-10">
                <section>
                    <div class="flex items-center gap-3 mb-3">
                        <div
                            class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center font-black text-sm shadow-lg shadow-primary/20">
                            1
                        </div>
                        <h2 class="text-lg font-[900] text-[#1E293B]">训练形象视频</h2>
                    </div>

                    <div class="rounded-2xl overflow-hidden">
                        <upload
                            class="w-full"
                            type="video"
                            drag
                            show-progress
                            :limit="1"
                            :accept="commonUploadLimit.videoExtension.join(',')"
                            :show-file-list="false"
                            :max-size="commonUploadLimit.size"
                            :video-max-width="commonUploadLimit.maxWidthResolution"
                            :video-max-height="commonUploadLimit.maxHeightResolution"
                            :max-duration="commonUploadLimit.videoMaxDuration"
                            :min-duration="commonUploadLimit.videoMinDuration"
                            @change="handleUploadChange($event, 'anchor')">
                            <div
                                class="bg-[#F8FAFC] min-h-[260px] flex flex-col items-center justify-center p-6 group transition-all w-full"
                                v-loading="anchorParseLoading">
                                <div
                                    class="w-full h-full relative rounded-xl overflow-hidden shadow-inner"
                                    v-if="anchorData.pic">
                                    <video :src="anchorData.url" class="w-full max-h-[300px] bg-black" controls />
                                    <button
                                        class="absolute top-3 right-3 w-8 h-8 bg-black/50 hover:bg-red-500 text-white rounded-full flex items-center justify-center transition-colors"
                                        @click.stop="clearAnchorData">
                                        <Icon name="el-icon-Close" />
                                    </button>
                                </div>
                                <div class="text-center" v-else>
                                    <div
                                        class="w-16 h-16 bg-[#0065fb]/10 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                                        <Icon name="el-icon-VideoCamera" :size="32" color="var(--el-color-primary)" />
                                    </div>
                                    <ElButton type="primary" class="!rounded-xl px-8 !h-11 font-bold"
                                        >上传形象视频</ElButton
                                    >
                                    <div class="mt-6 grid grid-cols-2 gap-x-8 gap-y-2 text-left max-w-[400px] mx-auto">
                                        <div class="text-[11px] text-[#94A3B8] flex items-start gap-1">
                                            <div class="w-1 h-1 rounded-full bg-primary mt-1.5 flex-shrink-0"></div>
                                            时长：{{ commonUploadLimit.videoMinDuration }}-{{
                                                commonUploadLimit.videoMaxDuration
                                            }}秒
                                        </div>
                                        <div class="text-[11px] text-[#94A3B8] flex items-start gap-1">
                                            <div class="w-1 h-1 rounded-full bg-primary mt-1.5 flex-shrink-0"></div>
                                            帧率：25fps / H.264
                                        </div>
                                        <div class="text-[11px] text-[#94A3B8] flex items-start gap-1">
                                            <div class="w-1 h-1 rounded-full bg-primary mt-1.5 flex-shrink-0"></div>
                                            大小：≤{{ commonUploadLimit.size }}MB
                                        </div>
                                        <div class="text-[11px] text-[#94A3B8] flex items-start gap-1">
                                            <div class="w-1 h-1 rounded-full bg-primary mt-1.5 flex-shrink-0"></div>
                                            单边：≤{{ commonUploadLimit.maxWidthResolution }}px
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </upload>
                    </div>
                </section>

                <section>
                    <div class="flex items-center gap-3 mb-6">
                        <div
                            class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center font-black text-sm shadow-lg shadow-primary/20">
                            2
                        </div>
                        <h2 class="text-lg font-[900] text-[#1E293B]">人身核验与授权</h2>
                    </div>

                    <div class="rounded-2xl overflow-hidden">
                        <upload
                            drag
                            type="video"
                            show-progress
                            :show-file-list="false"
                            :accept="commonUploadLimit.videoExtension.join(',')"
                            :max-size="commonUploadLimit.size"
                            :max-duration="120"
                            :min-duration="1"
                            :video-max-width="commonUploadLimit.maxWidthResolution"
                            :video-max-height="commonUploadLimit.maxHeightResolution"
                            @change="handleUploadChange($event, 'auth')">
                            <div class="bg-[#F8FAFC] min-h-[220px] p-8 w-full" v-loading="authParseLoading">
                                <div
                                    class="w-full h-full relative rounded-xl overflow-hidden shadow-inner"
                                    v-if="authData.url">
                                    <video :src="authData.url" class="w-full max-h-[200px] bg-black" controls />
                                    <button
                                        class="absolute top-3 right-3 w-8 h-8 bg-black/50 hover:bg-red-500 text-white rounded-full flex items-center justify-center transition-colors"
                                        @click.stop="clearAuthData">
                                        <Icon name="el-icon-Close" />
                                    </button>
                                </div>
                                <div v-else class="flex flex-col items-center">
                                    <div
                                        class="w-full max-w-[420px] bg-white border border-[#E2E8F0] rounded-2xl p-5 mb-6">
                                        <div class="flex items-center gap-2 mb-3 text-primary">
                                            <Icon name="el-icon-Warning" />
                                            <span class="text-[13px] font-[900]">请录制以下授权文案</span>
                                        </div>
                                        <p
                                            class="text-[13px] text-[#64748B] leading-relaxed font-medium bg-[#F8FAFC] p-4 rounded-xl italic border-l-4 border-[#0065FB]/20">
                                            “我是xxx(真实姓名)，我授权{{
                                                shanjianAuth
                                            }}使用视频中的肖像、声音，生成定制数字人，并在本人账号中创作使用。”
                                        </p>
                                    </div>
                                    <ElButton type="primary" plain class="!rounded-xl px-10 !h-11 font-bold bg-white">
                                        <Icon name="el-icon-Upload" />
                                        <span class="ml-2">上传授权视频</span>
                                    </ElButton>
                                </div>
                            </div>
                        </upload>
                    </div>
                </section>

                <div class="pt-6 border-t border-br">
                    <button
                        class="w-full h-[60px] rounded-2xl bg-[#1E293B] text-white font-[900] text-lg flex items-center justify-center gap-3 transition-all hover:bg-black active:scale-[0.98] disabled:opacity-30 disabled:grayscale"
                        :disabled="!anchorData.url || !authData.url"
                        @click="lockFn">
                        <Icon v-if="isLock" name="el-icon-Loading" />
                        立即开启克隆
                        <span class="text-sm font-bold opacity-60">(消耗 {{ getToken }} 算力)</span>
                    </button>
                    <div class="flex items-center justify-center gap-2 mt-4 text-[12px] text-[#94A3B8]">
                        <Icon name="el-icon-CircleCheck" color="var(--green-500)" />
                        <span>点击即代表同意</span>
                        <router-link
                            :to="`/policy/${PolicyAgreementEnum.PRIVACY}`"
                            target="_blank"
                            class="text-primary font-bold hover:underline"
                            >《隐私协议政策》</router-link
                        >
                    </div>
                </div>
            </div>

            <div class="w-full lg:w-[380px] space-y-3">
                <div class="bg-white rounded-[24px] border border-br p-6">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-6 h-6 rounded-lg bg-[#FFF3E0] text-[#F59E0B] flex items-center justify-center">
                            <Icon name="el-icon-VideoPlay" />
                        </div>
                        <span class="text-[15px] font-[900] text-[#1E293B]">标准拍摄演示</span>
                    </div>
                    <div class="shadow-light rounded-[24px]">
                        <video-player
                            :video-url="getApiUrl() + '/static/videos/dh_example2.mp4'"
                            :poster="getApiUrl() + '/static/images/dh_example_bg2.png'"></video-player>
                    </div>
                </div>

                <div class="bg-white rounded-[24px] border border-br p-6">
                    <div class="flex items-center gap-2 mb-5">
                        <div class="w-6 h-6 rounded-lg bg-[#F0FDF4] text-[#16A34A] flex items-center justify-center">
                            <Icon name="el-icon-List" />
                        </div>
                        <span class="text-[15px] font-[900] text-[#1E293B]">拍摄避坑指南</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mb-6">
                        <div
                            v-for="item in exampleErrorLists"
                            class="flex flex-col items-center bg-[#FFF8F8] border border-[#FFE4E4] rounded-2xl p-3">
                            <img :src="item.image" class="w-12 h-12 grayscale-[0.5] mb-2" />
                            <span class="text-[11px] font-black text-[#EF4444]/70">{{ item.text }}</span>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="(item, index) in uploadTemplateContentLists"
                            class="flex items-center justify-between p-3 bg-[#F8FAFC] rounded-xl border border-[#E2E8F0]/50">
                            <span class="text-[12px] font-bold text-[#64748B]">{{ item.name }}</span>
                            <span class="text-[12px] font-black text-[#1E293B]">{{ item.value }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import * as MP4Box from "@/assets/js/mp4box.all.js";
import dayjs from "dayjs";
import { useAppStore } from "@/stores/app";
import { useUserStore } from "@/stores/user";
import { uploadImage, videoTranscode } from "@/api/app";
import { batchCloneAnchor, createShanjianAnchor } from "@/api/digital_human";
import { commonUploadLimit } from "@/pages/app/digital_human/_hooks/useUpload";
import { TokensSceneEnum, PolicyAgreementEnum } from "@/enums/appEnums";
import { getApiUrl } from "@/utils/env";
import { DigitalHumanModelVersionEnum, SidebarTypeEnum } from "@/pages/app/digital_human/_enums";
import VideoPlayer from "@/pages/app/digital_human/_components/video-player.vue";
import exampleError1 from "@/pages/app/digital_human/_assets/images/example_error1.png";
import exampleError2 from "@/pages/app/digital_human/_assets/images/example_error2.png";
import exampleError3 from "@/pages/app/digital_human/_assets/images/example_error3.png";
import exampleError4 from "@/pages/app/digital_human/_assets/images/example_error4.png";

const appStore = useAppStore();
const userStore = useUserStore();
const { userTokens } = toRefs(userStore);
const isOssTranscode = computed(() => appStore.config?.is_oss_transcode);

const anchorData = reactive({
    name: dayjs().format("YYYYMMDDHHmm"),
    pic: "",
    url: "",
    width: 0,
    height: 0,
    anchor_id: "",
    isH264: false,
});

const authData = reactive<any>({
    name: dayjs().format("YYYYMMDDHHmm"),
    pic: "",
    url: "",
    isH264: false,
});

const anchorParseLoading = ref<boolean>(false);
const authParseLoading = ref<boolean>(false);

const shanjianAuth = computed(() => appStore.getDigitalHumanConfig.shanjian_auth);
const getToken = computed(() => {
    const token1 = userStore.getTokenByScene(TokensSceneEnum.HUMAN_AVATAR_SHANJIAN)?.score;
    const token2 = userStore.getTokenByScene(TokensSceneEnum.HUMAN_AVATAR_CHANJING)?.score;
    const token3 = userStore.getTokenByScene(TokensSceneEnum.HUMAN_AVATAR)?.score;
    return parseFloat(token1) + parseFloat(token2) + parseFloat(token3);
});

// 上传模板内容列表
const uploadTemplateContentLists = computed(() => {
    return [
        { name: "视频方向", value: "横向或纵向" },
        { name: "文件格式", value: commonUploadLimit.videoExtension.join("、") },
        { name: "分辨率", value: `最大支持${commonUploadLimit.maxWidthResolution / 1000}K` },
        { name: "文件大小", value: `小于${commonUploadLimit.size}MB` },
    ];
});

const exampleErrorLists = computed(() => {
    return [
        { image: exampleError1, text: "遮挡面部" },
        { image: exampleError2, text: "人脸出框" },
        { image: exampleError3, text: "侧脸拍摄" },
        { image: exampleError4, text: "多人出镜" },
    ];
});

const handleUploadChange = async (res: any, type: "anchor" | "auth") => {
    if (type === "anchor") {
        anchorParseLoading.value = true;
    } else {
        authParseLoading.value = true;
    }
    try {
        const { raw, response } = res;

        const { uri } = response.data;
        const { file, width, height } = await getVideoFirstFrame(uri);
        const imageRes = await uploadImage({
            file,
        });
        if (type === "anchor") {
            anchorData.pic = imageRes.uri;
            anchorData.width = width;
            anchorData.height = height;
            anchorData.url = uri;
        } else {
            authData.pic = imageRes.uri;
            authData.width = width;
            authData.height = height;
            authData.url = uri;
        }
    } catch (error) {
        feedback.msgError("解析视频失败");
    } finally {
        anchorParseLoading.value = false;
        authParseLoading.value = false;
    }
};

const checkIsH264 = async (file: File): Promise<boolean> => {
    return new Promise((resolve) => {
        const mp4boxfile: any = MP4Box.createFile();
        let isResolved = false;

        // 成功解析到元数据后的回调
        mp4boxfile.onReady = (info: any) => {
            if (isResolved) return;
            isResolved = true;
            const videoTrack = info.videoTracks[0];
            // 校验 H264 标识符 avc1
            resolve(!!videoTrack?.codec?.startsWith("avc1"));
        };

        // 错误处理
        mp4boxfile.onError = () => {
            if (isResolved) return;
            isResolved = true;
            resolve(false);
        };

        const reader = new FileReader();

        /**
         * 辅助函数：读取文件指定范围并喂给 MP4Box
         */
        const readChunk = (start: number, end: number) => {
            return new Promise((res) => {
                reader.onload = (e) => {
                    const buffer = e.target?.result as any;
                    buffer.fileStart = start; // 关键：告诉 mp4box 这段数据在文件中的物理位置
                    mp4boxfile.appendBuffer(buffer);
                    res(true);
                };
                reader.readAsArrayBuffer(file.slice(start, end));
            });
        };

        // 执行检测流
        (async () => {
            // 第一阶段：尝试读取头部 512KB (涵盖 95% 的 Fast-start 视频)
            await readChunk(0, 512 * 1024);

            // 给解析留一点 CPU 时间
            await new Promise((r) => setTimeout(r, 100));

            // 第二阶段：如果头部没读到 moov，尝试读取尾部 1MB (针对非 Fast-start 视频)
            if (!isResolved && file.size > 512 * 1024) {
                const tailStart = Math.max(512 * 1024, file.size - 1024 * 1024);
                await readChunk(tailStart, file.size);
            }

            // 第三阶段：如果还没读到，最后的倔强，读取文件中间（可选，通常前两步就够了）
            // 3秒后还没结果，直接返回 false
            setTimeout(() => {
                if (!isResolved) {
                    isResolved = true;
                    resolve(false);
                }
            }, 3000);
        })();
    });
};

const clearAnchorData = () => {
    anchorData.pic = "";
    anchorData.url = "";
    anchorData.width = 0;
    anchorData.height = 0;
};

const clearAuthData = () => {
    authData.pic = "";
    authData.url = "";
    authData.width = 0;
    authData.height = 0;
};

const handleClone = async () => {
    if (userTokens.value <= getToken.value) {
        feedback.msgPowerInsufficient();
        return;
    }
    try {
        await batchCloneAnchor({
            name: anchorData.name,
            width: anchorData.width,
            height: anchorData.height,
            anchor_url: anchorData.url,
            authorized_url: authData.url,
            pic: anchorData.pic,
            authorized_pic: authData.pic,
        });
        useNuxtApp().$confirm({
            title: "提示",
            message: "创建任务成功，是否前往我的形象查看进度",
            onConfirm: () => {
                navigateTo(`/app/digital_human?type=${SidebarTypeEnum.MY_ANCHOR}`);
            },
        });
        clearAnchorData();
        clearAnchorData();
    } catch (error) {
        feedback.msgError("克隆失败");
    }
};

const { isLock, lockFn } = useLockFn(handleClone);
</script>

<style scoped lang="scss">
:deep(.el-upload-dragger) {
    padding: 0;
    border: none;
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    @apply bg-[#E2E8F0] rounded-full;
}

.animate-spin {
    animation: spin 1s linear infinite;
}
</style>
