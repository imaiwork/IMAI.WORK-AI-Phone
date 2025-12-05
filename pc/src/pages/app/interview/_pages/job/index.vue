<template>
    <div class="h-full flex flex-col">
        <div class="rounded-lg px-[30px] h-[107px] bg-white flex items-center justify-between">
            <div>
                <div class="text-2xl">岗位列表</div>
                <div class="text-[#74798C] mt-1">创建你的岗位，全自动进行人员招聘</div>
            </div>
            <div>
                <ElButton
                    color="#FFC8A4"
                    class="!h-[40px] w-[120px]"
                    :loading="isLockShareLink"
                    @click="lockShareLink()">
                    <span class="text-[#653619]">分享链接</span>
                </ElButton>
                <ElButton type="primary" class="!h-[40px] w-[120px]" @click="handleAdd()">新增岗位</ElButton>
            </div>
        </div>
        <div
            class="grow min-h-0 flex flex-col mt-6 overflow-y-auto"
            :infinite-scroll-immediate="false"
            :infinite-scroll-disabled="!pager.isLoad"
            :infinite-scroll-distance="10"
            v-infinite-scroll="load">
            <template v-if="pager.lists.length > 0">
                <div class="grid grid-cols-3 gap-6 pb-4">
                    <div
                        v-for="(item, index) in pager.lists"
                        :key="index"
                        class="h-[168px] rounded-[6px] bg-white px-6 py-4 group relative cursor-pointer flex flex-col hover:shadow-lighter transition-all duration-300">
                        <div class="absolute right-10 top-3 z-[77] group-hover:visible invisible">
                            <ElButton link icon="el-icon-CopyDocument" @click="handleCopyLink(item.id)"
                                >复制链接</ElButton
                            >
                        </div>
                        <div class="flex items-center gap-4 mr-5">
                            <ElImage :src="item.avatar" lazy fit="cover" class="w-10 h-10 rounded-[5px]" />
                            <span class="text-lg">{{ item.name }}</span>
                        </div>
                        <div class="grow">
                            <div class="line-clamp-3 text-xs text-[#524B6B] mt-3 leading-5">
                                {{ item.desc }}
                            </div>
                        </div>
                        <div class="text-[10px] flex items-center justify-between text-[#AAA6B9] mt-3">
                            <div>
                                {{ dayjs(item.create_time).format("YYYY/MM/DD") }}
                                发布
                            </div>
                            <div>面试人数：{{ item.interview_user_num || 0 }}</div>
                        </div>
                        <div
                            class="absolute right-2 top-2 z-[1000] invisible group-hover:visible"
                            :class="[activeJob == item.id ? '!visible' : '']">
                            <ElPopover
                                :show-arrow="false"
                                popper-class="!w-[130px] !min-w-[130px] !p-[6px] !rounded-xl"
                                @show="visibleChange(true, item.id)"
                                @hide="visibleChange(false, item.id)">
                                <template #reference>
                                    <div class="rotate-90 origin-center p-1">
                                        <Icon name="el-icon-MoreFilled"></Icon>
                                    </div>
                                </template>
                                <div class="flex flex-col gap-2">
                                    <div
                                        class="px-2 py-1 hover:bg-primary-light-9 rounded-lg cursor-pointer"
                                        @click="handleEdit(item.id)">
                                        <ElButton link icon="el-icon-Edit" class="w-full !justify-start"
                                            >编辑岗位</ElButton
                                        >
                                    </div>
                                    <div
                                        class="px-2 py-1 hover:bg-primary-light-9 rounded-lg cursor-pointer"
                                        @click="handleRpaSetting(item)">
                                        <ElButton link icon="el-icon-Setting" class="w-full !justify-start"
                                            >RPA设置</ElButton
                                        >
                                    </div>
                                    <div
                                        class="px-2 py-1 hover:bg-primary-light-9 rounded-lg cursor-pointer"
                                        @click="handleInterviewRecord(item.id)">
                                        <ElButton link icon="el-icon-VideoCamera" class="w-full !justify-start"
                                            >面试记录</ElButton
                                        >
                                    </div>
                                    <div
                                        class="px-2 py-1 hover:bg-primary-light-9 rounded-lg cursor-pointer"
                                        @click="handleDelete(item.id, index)">
                                        <ElButton link icon="el-icon-Delete" class="w-full !justify-start"
                                            >删除</ElButton
                                        >
                                    </div>
                                </div>
                            </ElPopover>
                        </div>
                    </div>
                </div>
                <div v-if="!pager.isLoad" class="text-center py-4 text-gray-500">暂无更多了</div>
            </template>
            <div v-else class="grow flex items-center justify-center bg-white rounded-xl">
                <ElEmpty description="暂无数据"></ElEmpty>
            </div>
        </div>
        <edit-pop v-if="showEditPop" ref="editPopRef" @close="showEditPop = false" @success="resetPage()" />
        <rpa-edit-pop
            v-if="showRpaEditPopup"
            ref="rpaEditRef"
            @close="showRpaEditPopup = false"
            @success="resetPage()" />
    </div>
</template>

<script setup lang="ts">
import { getJobList, deleteJob, generateJobAllLink, generateJobLink } from "@/api/interview";
import { dayjs } from "element-plus";
import EditPop from "./_components/edit-pop.vue";
import RpaEditPop from "./_components/rpa-edit.vue";

const router = useRouter();

const { copy } = useCopy();
const nuxtApp = useNuxtApp();
const showEditPop = ref<boolean>(false);
const editPopRef = shallowRef<InstanceType<typeof EditPop>>();
const rpaEditRef = shallowRef<InstanceType<typeof RpaEditPop>>();
const showRpaEditPopup = ref<boolean>(false);
const queryParams = reactive({
    page_no: 1,
});

const { pager, getLists, resetPage } = usePaging({
    fetchFun: getJobList,
    params: queryParams,
    isScroll: true,
});

const activeJob = ref<number | undefined>();
const visibleChange = (flag: boolean, id: number) => {
    if (!flag) {
        activeJob.value = undefined;
    } else {
        activeJob.value = id;
    }
};

const handleShareLink = async () => {
    try {
        const { url } = await generateJobAllLink();
        copy(url, { successMsg: "复制链接成功" });
    } catch (error) {
        feedback.msgError(error);
    }
};

const { lockFn: lockShareLink, isLock: isLockShareLink } = useLockFn(handleShareLink);

const handleAdd = async () => {
    showEditPop.value = true;
    await nextTick();
    editPopRef.value?.open();
};

const handleEdit = async (id: number) => {
    showEditPop.value = true;
    await nextTick();
    editPopRef.value?.open("edit");
    editPopRef.value?.getDetail(id);
};

const handleRpaSetting = async (row: any) => {
    const { interview_config } = row;
    showRpaEditPopup.value = true;
    await nextTick();
    rpaEditRef.value?.open();
    rpaEditRef.value?.setFormData({
        ...interview_config,
        job_id: row.id,
    });
};

const handleInterviewRecord = async (id: number) => {
    router.replace(`/app/interview?type=2&id=${id}`);
    setTimeout(() => {
        window.location.reload();
    }, 100);
};

const handleCopyLink = async (id: number) => {
    feedback.loading("复制中...");
    try {
        const { url } = await generateJobLink({ job_id: id });
        if (url) {
            copy(url, { successMsg: "复制链接成功" });
        } else {
            feedback.msgError("小程序未配置，请联系站长");
        }
    } catch (error) {
        feedback.msgError(error);
    } finally {
        feedback.closeLoading();
    }
};

const handleDelete = async (id: number, index) => {
    nuxtApp.$confirm({
        message: "是否删除该岗位？",
        onConfirm: async () => {
            await deleteJob({ id });
            pager.lists.splice(index, 1);
        },
    });
};

const load = async () => {
    queryParams.page_no += 1;
    await getLists();
};

getLists();
</script>

<style scoped></style>
