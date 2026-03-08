<template>
    <div class="h-full flex flex-col min-w-[1000px]">
        <div
            class="rounded-[20px] px-8 h-[100px] bg-[#FFFFFF] border border-br flex items-center justify-between shadow-sm">
            <div class="flex flex-col gap-1">
                <div class="text-2xl font-[900] text-[#0F172A] tracking-tight">岗位管理</div>
                <div class="text-[13px] text-[#64748B] font-medium">创建岗位并开启全自动 AI 招聘流程</div>
            </div>
            <div class="flex items-center gap-3">
                <ElButton
                    class="!h-11 !px-6 !rounded-xl !border-none !bg-[#FFF7ED] !text-[#C2410C] font-black hover:!bg-[#FFEDD5] transition-all"
                    :loading="isLockShareLink"
                    @click="lockShareLink()">
                    <div class="flex items-center gap-2">
                        <Icon name="el-icon-Share" color="#C2410C" :size="16" />
                        <span>分享大厅链接</span>
                    </div>
                </ElButton>
                <ElButton
                    type="primary"
                    class="!h-11 !px-6 !rounded-xl !font-black hover:scale-105 transition-all"
                    @click="handleAdd()">
                    <div class="flex items-center gap-2">
                        <Icon name="el-icon-Plus" color="#FFFFFF" :size="16" />
                        <span>新增岗位</span>
                    </div>
                </ElButton>
            </div>
        </div>

        <div class="grow min-h-0 mt-3" v-spin="{ show: loading }">
            <ElScrollbar :distance="20" @end-reached="load" v-if="pager.lists.length > 0">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 pb-10">
                    <div
                        v-for="(item, index) in pager.lists"
                        :key="index"
                        class="group relative h-[180px] rounded-[20px] bg-[#FFFFFF] border border-br px-6 py-5 cursor-pointer flex flex-col hover:shadow-xl hover:border-[#4F46E533] transition-all duration-300">
                        <div
                            class="absolute right-12 top-4 z-[10] opacity-0 group-hover:opacity-100 transition-opacity"
                            @click.stop="handleCopyLink(item.id)">
                            <div
                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#F1F0FF] text-[#4F46E5] text-[11px] font-medium hover:bg-[#4F46E5] hover:text-[#FFFFFF] transition-colors">
                                <Icon name="el-icon-Link" :size="12" />
                                <span>复制链接</span>
                            </div>
                        </div>

                        <div class="flex gap-4">
                            <div class="p-1 rounded-xl bg-slate-50 border border-[#F1F5F9] shrink-0">
                                <ElImage :src="item.avatar" lazy fit="cover" class="w-12 h-12 rounded-lg" />
                            </div>
                            <div class="min-w-0">
                                <span class="text-lg font-[900] text-[#0F172A] truncate">{{ item.name }}</span>
                            </div>
                        </div>

                        <div class="grow mt-3">
                            <div class="line-clamp-2 text-xs text-[#64748B] leading-relaxed font-medium">
                                {{ item.desc || "暂无岗位详细描述信息" }}
                            </div>
                        </div>

                        <div class="flex items-center justify-between mt-4 pt-4 border-t border-[#F8FAFC]">
                            <div class="flex items-center gap-2 text-[11px] font-medium text-[#94A3B8]">
                                <Icon name="el-icon-Calendar" :size="12" color="#CBD5E1" />
                                <span>{{ dayjs(item.create_time).format("YYYY/MM/DD") }} 发布</span>
                            </div>
                            <div class="px-2 py-0.5 rounded-md bg-[#F0FDF4] text-[#16A34A] text-[11px] font-black">
                                面试人数：{{ item.interview_user_num || 0 }}
                            </div>
                        </div>

                        <div class="absolute right-2 top-3 group-hover:visible transition-all">
                            <ElPopover
                                :show-arrow="false"
                                popper-class="!rounded-[16px] !border-[#F1F5F9] !p-1.5 !shadow-light"
                                @show="visibleChange(true, item.id)"
                                @hide="visibleChange(false, item.id)">
                                <template #reference>
                                    <div
                                        class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-slate-50 transition-colors cursor-pointer"
                                        @click.stop>
                                        <Icon name="el-icon-MoreFilled" color="#94A3B8" :size="14"></Icon>
                                    </div>
                                </template>

                                <div class="flex flex-col p-1 gap-1">
                                    <div class="table-action-item" @click="handleEdit(item.id)">
                                        <Icon name="el-icon-Edit" :size="14" />
                                        <span>编辑岗位</span>
                                    </div>
                                    <div class="table-action-item" @click="handleRpaSetting(item)" v-if="false">
                                        <Icon name="el-icon-Setting" :size="14" />
                                        <span>RPA 配置</span>
                                    </div>
                                    <div class="table-action-item" @click="handleInterviewRecord(item.id)">
                                        <Icon name="el-icon-VideoCamera" :size="14" />
                                        <span>面试记录</span>
                                    </div>
                                    <div class="h-[1px] bg-[#F1F5F9] my-1"></div>
                                    <div
                                        class="table-action-item !text-red-500 hover:!bg-red-50"
                                        @click="handleDelete(item.id, index)">
                                        <Icon name="el-icon-Delete" :size="14" />
                                        <span>删除岗位</span>
                                    </div>
                                </div>
                            </ElPopover>
                        </div>
                    </div>
                </div>
                <load-text :is-load="pager.isLoad"></load-text>
            </ElScrollbar>
            <div v-else class="grow flex flex-col items-center justify-center">
                <ElEmpty description="尚未创建任何招聘岗位" />
                <ElButton type="primary" link @click="handleAdd" class="!font-black mt-[-20px]">立即点击创建</ElButton>
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
import { SidebarTypeEnum } from "@/pages/app/internal/_enums";
import { dayjs } from "element-plus";
import EditPop from "./_components/edit-pop.vue";
import RpaEditPop from "./_components/rpa-edit.vue";

const loading = ref<boolean>(true);

const router = useRouter();
const { show, hide } = useGlobalSpin();
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
    router.replace(`/app/internal?type=${SidebarTypeEnum.INTERVIEW_RECORD}&id=${id}`);
};

const handleCopyLink = async (id: number) => {
    show({ text: "复制中..." });
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
        hide();
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

const load = async (e: string) => {
    if (e === "bottom") {
        if (!pager.isLoad || pager.loading) return;
        queryParams.page_no += 1;
        await getLists();
    }
};

const init = async () => {
    try {
        await getLists();
    } finally {
        loading.value = false;
    }
};

init();
</script>
