<template>
    <ElDrawer v-model="show" body-class="bg-app-bg-2 !p-0" size="450px" :with-header="false">
        <div class="h-full flex flex-col" ref="containerRef">
            <div class="absolute w-6 h-6 top-5 right-2" @click="close">
                <close-btn />
            </div>
            <div class="text-[15px] font-bold text-white pt-[23px] px-[26px]">任务详情</div>
            <div class="mt-[38px] px-[26px]">
                <div class="text-[15px] font-bold text-white mb-[14px]">{{ detail?.name }}</div>
                <div class="flex">
                    <div class="text-[#ffffff66] flex-shrink-0">任务类型：</div>
                    <div class="text-white">{{ detail?.task_category }}</div>
                </div>
                <div class="flex mt-2">
                    <div class="text-[#ffffff66] flex-shrink-0">发布账号：</div>
                    <div class="flex gap-2">
                        <img v-if="getPlatformIcon" :src="getPlatformIcon" class="w-4 h-4" />
                        <span class="text-white">{{ detail?.account_name }}（设备：{{ detail?.device_name }}）</span>
                    </div>
                </div>
                <div class="flex mt-2">
                    <div class="text-[#ffffff66] flex-shrink-0">发布数量（每天）：</div>
                    <div class="text-white">{{ detail?.count || 0 }}条</div>
                </div>
                <div class="flex mt-2">
                    <div class="text-[#ffffff66] flex-shrink-0">创建时间：</div>
                    <div class="text-white">{{ detail?.create_time || "-" }}</div>
                </div>
            </div>
            <div class="grow min-h-0 mt-5">
                <ElScrollbar v-if="dataLists.length > 0">
                    <div class="px-[26px]">
                        <div class="flex flex-col gap-y-3">
                            <div
                                v-for="(item, index) in dataLists"
                                :key="index"
                                class="border border-app-border-1 rounded-md px-[23px]">
                                <div
                                    class="h-10 flex items-center border-0 border-b border-app-border-1 text-[#ffffff80]">
                                    <template v-if="item.material_type == 2"> 第{{ index + 1 }}组图文发布 </template>
                                    <template v-if="item.material_type == 1"> 第{{ index + 1 }}个视频发布 </template>
                                </div>
                                <div class="py-2">
                                    <div class="text-white">
                                        {{ item.material_title }}
                                    </div>
                                    <div class="mt-2 text-[11px] text-white">
                                        {{ item.material_subtitle }}
                                    </div>
                                    <div class="mt-[10px] flex flex-wrap gap-2 text-[11px] text-[#ffffff80]">
                                        <div v-for="topic in item.material_tag" :key="topic">#{{ topic }}</div>
                                    </div>
                                    <div class="grid grid-cols-4 gap-3 mt-3" v-if="item.material_type == 2">
                                        <ElImage
                                            v-for="img in item.material_url.split(',')"
                                            class="h-[80px] rounded-md"
                                            fit="cover"
                                            :src="img"
                                            :preview-src-list="item.material_url.split(',')"
                                            preview-teleported />
                                    </div>
                                    <div class="mt-3" v-if="item.material_type == 1">
                                        <video
                                            ref="videoRef"
                                            :src="item.material_url"
                                            :poster="item.pic"
                                            class="h-auto w-full rounded-md object-cover"
                                            controls
                                            @play="handlePlay(index)" />
                                    </div>
                                    <div class="flex mt-[17px]">
                                        <div class="text-[#ffffff66]">任务状态：</div>
                                        <div>
                                            <span v-if="item.status == 0" class="text-info">未发布</span>
                                            <span v-if="item.status == 1" class="text-[#3BB840]">已发布</span>
                                            <span v-if="item.status == 2" class="text-danger"
                                                >发布失败({{ item.remark }})</span
                                            >
                                            <span v-if="item.status == 3" class="text-primary">发布中</span>
                                        </div>
                                    </div>
                                    <div class="flex mt-2">
                                        <div class="text-[#ffffff66]">发布时间：</div>
                                        <div class="text-white">{{ item.publish_time }}</div>
                                    </div>
                                    <div class="flex justify-end">
                                        <ElButton
                                            color="#121212"
                                            class="!border-app-border-1"
                                            @click="handleDelete(item.id)"
                                            >删除</ElButton
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </ElScrollbar>
                <div class="my-10" v-else>
                    <ElEmpty />
                </div>
            </div>
        </div>
    </ElDrawer>
</template>

<script setup lang="ts">
import { getDeviceAccountTaskDetail, getDeviceTaskRecordList, deleteDeviceTaskRecord } from "@/api/device";

const emit = defineEmits<{
    (e: "close"): void;
}>();

const { getPlatform } = useSocialPlatform();

const containerRef = shallowRef();
const videoRef = shallowRef();

const show = ref(false);

const currRow = ref<any>({});

const detail = ref<any>({});
const dataLists = ref([]);

const getPlatformIcon = computed(() => getPlatform(detail.value?.account_type)?.icon);

const handlePlay = (index: number) => {
    if (!videoRef.value || !Array.isArray(videoRef.value)) return;
    // 仅暂停当前正在播放的视频，避免遍历所有视频元素造成性能问题
    const currentVideo = videoRef.value[index];
    if (currentVideo) {
        // 先暂停其他可能正在播放的视频（仅处理已初始化的）
        videoRef.value.forEach((item: HTMLVideoElement) => {
            if (item && item !== currentVideo && !item.paused) {
                item.pause();
            }
        });
        currentVideo.play();
    }
};

const handleDelete = (id: number) => {
    useNuxtApp().$confirm({
        message: "确定要删除吗？",
        theme: "dark",
        onConfirm: async () => {
            try {
                feedback.loading("删除中...", containerRef.value);
                await deleteDeviceTaskRecord({ id });
                feedback.msgSuccess("删除成功");
                getLists();
            } catch (error) {
                feedback.msgError(error || "删除失败");
            } finally {
                feedback.closeLoading();
            }
        },
    });
};

const open = (row: any) => {
    show.value = true;
    currRow.value = row;
    getDetail(row.id);

    getLists();
};

const close = () => {
    show.value = false;
    emit("close");
};

const getLists = async () => {
    const { lists } = await getDeviceTaskRecordList({
        page_size: 9999,
        task_type: 3,
        publish_id: currRow.value?.publish_id,
        account: currRow.value?.account,
    });
    dataLists.value = lists;
};

const getDetail = async (id: number) => {
    const data = await getDeviceAccountTaskDetail({ id });
    detail.value = data;
};

defineExpose({
    open,
    getDetail,
});
</script>

<style scoped></style>
