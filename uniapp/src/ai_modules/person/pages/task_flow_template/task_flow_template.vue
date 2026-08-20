<template>
    <view class="h-screen bg-[#f8f9fa] flex flex-col">
        <u-navbar
            :border-bottom="false"
            :background="{ background: '#ffffff' }"
            title="工作流模板"
            title-bold
            :custom-back="back"></u-navbar>
        <!-- <view class="bg-white px-[24rpx] py-[20rpx] shadow-sm flex-shrink-0">
            <scroll-view scroll-x class="w-full">
                <view class="flex gap-[16rpx] py-[4rpx]">
                    <template v-if="isCategoryLoading">
                        <view
                            v-for="i in 4"
                            :key="i"
                            class="px-[40rpx] py-[12rpx] rounded-full bg-[#F3F4F6] flex-shrink-0" />
                    </template>

                    <template v-else>
                        <view
                            v-for="(cat, cIdx) in displayCategories"
                            :key="cIdx"
                            class="px-[28rpx] py-[12rpx] rounded-full text-[24rpx] font-medium transition-colors flex-shrink-0"
                            :class="
                                activeCategory.name === cat.name
                                    ? 'bg-primary text-white'
                                    : 'bg-[#F3F4F6] text-[#4B5563]'
                            "
                            @click="setActiveCategory(cat)">
                            <text>{{ cat.name }}</text>
                        </view>
                    </template>
                </view>
            </scroll-view>
        </view> -->

        <z-paging
            ref="pagingRef"
            v-model="templateList"
            :fixed="false"
            :default-page-size="10"
            @query="fetchTemplateList"
            class="flex-1">
            <template #top>
                <view
                    class="mx-[30rpx] mt-[24rpx] border-2 border-dashed border-[#93B9FF] bg-[#EFF6FF] rounded-[32rpx] py-[28rpx] flex items-center justify-center gap-[12rpx] active:bg-[#E0EAFF] transition-colors"
                    @click="openCreateTplModal">
                    <u-icon name="plus" color="#2563EB" size="32" />
                    <text class="text-[28rpx] font-extrabold text-[#2563EB]">新增自定义工作流</text>
                </view>
            </template>

            <view class="px-[30rpx] pt-[24rpx] flex flex-col gap-[24rpx] pb-[24rpx]">
                <view
                    v-for="tpl in templateList"
                    :key="tpl.id"
                    class="bg-white rounded-[32rpx] px-[32rpx] py-[32rpx] relative overflow-hidden transition-all"
                    :style="
                        currentTemplateId === tpl.id
                            ? 'border:4rpx solid #3B82F6;box-shadow:0 0 0 8rpx rgba(59,130,246,0.1);'
                            : 'border:2rpx solid transparent;box-shadow:0 2rpx 8rpx rgba(0,0,0,0.06);'
                    "
                    @click="handleDetailTemplate(tpl.id)">
                    <view
                        v-if="tpl.type === TemplateTypeEnum.FIXED"
                        class="absolute top-0 right-0 bg-primary text-white text-[20rpx] px-[16rpx] py-[8rpx] rounded-bl-[16rpx] flex items-center gap-[6rpx]">
                        <u-icon name="lock" color="#ffffff" size="18" />
                        <text>IP专属</text>
                    </view>

                    <view
                        v-if="tpl.type === TemplateTypeEnum.EDITABLE && currentTemplateId !== tpl.id"
                        class="absolute top-[24rpx] right-[24rpx] w-[48rpx] h-[48rpx] flex items-center justify-center bg-[#FEF2F2] rounded-full"
                        @click.stop="handleDeleteTemplate(tpl.id, tpl.name)">
                        <u-icon name="trash" color="#EF4444" size="24" />
                    </view>

                    <view class="flex items-start justify-between mb-[12rpx] pr-[100rpx]">
                        <text class="text-[30rpx] font-extrabold text-[#111827]">{{ tpl.name }}</text>
                    </view>

                    <view class="flex items-center gap-[12rpx] mb-[20rpx] flex-wrap">
                        <view class="px-[14rpx] py-[4rpx] rounded bg-[#F3F4F6]">
                            <text class="text-[20rpx] text-[#4B5563]">{{ tpl.industry }}</text>
                        </view>
                        <focus-badge :focus="tpl.focus" />
                    </view>

                    <text class="text-[24rpx] text-[#6B7280] leading-relaxed line-clamp-2 mb-[24rpx]">
                        {{ tpl.desc }}
                    </text>

                    <view class="flex items-center justify-between pt-[20rpx] border-t border-[#F9FAFB]">
                        <text class="text-[22rpx] text-[#9CA3AF] font-medium">
                            包含 {{ tpl.schedule_count }} 个任务节点
                        </text>
                        <text
                            class="text-[24rpx] font-extrabold"
                            :class="currentTemplateId === tpl.id ? 'text-[#3B82F6]' : 'text-[#111827]'"
                            @click.stop="handleSwitchTemplate(tpl.id)">
                            {{ currentTemplateId === tpl.id ? "使用中" : "套用模板" }}
                        </text>
                    </view>
                </view>
            </view>

            <template #empty>
                <view class="flex flex-col items-center justify-center py-[80rpx]">
                    <text class="text-[26rpx] text-[#9CA3AF]">该分类暂无模板</text>
                </view>
            </template>
        </z-paging>
    </view>

    <u-popup v-model="showCreateTplModal" mode="center" width="85%" border-radius="32">
        <view class="p-[48rpx]">
            <view class="flex items-center justify-between mb-[48rpx]">
                <text class="text-[34rpx] font-extrabold text-[#111827]">新建自定义模板</text>
                <view
                    class="w-[56rpx] h-[56rpx] flex items-center justify-center bg-[#F3F4F6] rounded-full"
                    @click="closeCreateTplModal">
                    <u-icon name="close" color="#6B7280" size="28" />
                </view>
            </view>

            <view
                v-if="createTplError"
                class="flex items-start gap-[8rpx] bg-[#FEF2F2] text-[#DC2626] text-[22rpx] px-[24rpx] py-[20rpx] rounded-[16rpx] mb-[32rpx]">
                <u-icon name="error-circle" color="#DC2626" size="24" class="flex-shrink-0" />
                <text>{{ createTplError }}</text>
            </view>

            <view>
                <text class="text-[24rpx] font-bold text-[#374151] mb-[12rpx] block">模板名称</text>
                <input
                    v-model="newTplName"
                    maxlength="30"
                    class="bg-[#F9FAFB] border border-[#E5E7EB] rounded-[20rpx] px-[24rpx] py-[24rpx] text-[26rpx] text-[#111827]"
                    placeholder="例如：我的周末专属引流流（最多20字）"
                    placeholder-class="text-[#9CA3AF]" />
            </view>

            <view
                class="w-full rounded-[20rpx] py-[28rpx] mt-[48rpx] flex items-center justify-center active:opacity-80 transition-opacity"
                :class="isCreating ? 'bg-[#93C5FD]' : 'bg-[#2563EB]'"
                style="box-shadow: 0 8rpx 24rpx rgba(37, 99, 235, 0.3)"
                @click="handleCreateTemplate">
                <u-loading v-if="isCreating" mode="circle" color="#ffffff" size="28" />
                <text v-else class="text-white text-[28rpx] font-extrabold">确认创建</text>
            </view>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
import FocusBadge from "@/ai_modules/person/components/focus-badge/focus-badge.vue";
import {
    getTaskWorkTemplateList as getTaskWorkTemplateListApi,
    getTaskWorkTemplateCategoryList as getTaskWorkTemplateCategoryListApi,
} from "@/api/person";
import {
    useTaskStore,
    TemplateTypeEnum,
    IndustryEnum,
    type CategoryItem,
    type TemplateItem,
    type TemplateRaw,
    toTemplateItem,
} from "../task_flow/stores/taskStore";

// ─── Store（仅用于操作方法与当前模板状态）─────────────────────
const taskStore = useTaskStore();

// ─── 路由参数 ──────────────────────────────────────────────────
const personId = ref<string>("");

// ─── 固定"全部"分类 ────────────────────────────────────────────
const ALL_CATEGORY: CategoryItem = { id: 0, name: "全部" };

// ─── 分类状态（本地维护）──────────────────────────────────────
const isCategoryLoading = ref<boolean>(false);
const categories = ref<CategoryItem[]>([]);
const activeCategory = ref<CategoryItem>(ALL_CATEGORY);

const displayCategories = computed<CategoryItem[]>(() => [ALL_CATEGORY, ...categories.value]);
const isCustomCategory = computed(() => activeCategory.value.name === IndustryEnum.CUSTOM);

// ─── 模板列表（z-paging 托管）─────────────────────────────────
const pagingRef = ref<any>(null);
const templateList = ref<TemplateItem[]>([]);

// ─── 当前使用中的模板 ID（来自 Store）─────────────────────────
const currentTemplateId = computed(() => taskStore.currentTemplateId);

// ─── 弹窗状态 ──────────────────────────────────────────────────
const showCreateTplModal = ref<boolean>(false);
const newTplName = ref<string>("");
const createTplError = ref<string>("");
const isCreating = ref<boolean>(false);

// ─── 获取分类列表 ──────────────────────────────────────────────
const fetchCategoryList = async () => {
    isCategoryLoading.value = true;
    try {
        const res = await getTaskWorkTemplateCategoryListApi({
            persona_id: Number(personId.value),
        });
        if (res) categories.value = res;
    } finally {
        isCategoryLoading.value = false;
    }
};

const fetchTemplateList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getTaskWorkTemplateListApi({
            persona_id: Number(personId.value),
            category_id: activeCategory.value.id === 0 ? "" : activeCategory.value.id,
            page_no,
            page_size,
        });
        const list: TemplateItem[] = (lists ?? []).map((raw: TemplateRaw) => toTemplateItem(raw));
        pagingRef.value?.complete(list);
    } catch {
        pagingRef.value?.complete([]);
    }
};

// ─── 切换分类 Tab ──────────────────────────────────────────────
const setActiveCategory = (cat: CategoryItem) => {
    activeCategory.value = cat;
    pagingRef.value?.reload();
};

// ─── 跳转模板详情 ──────────────────────────────────────────────
const handleDetailTemplate = async (tplId: string) => {
    uni.$u.route({
        url: "/ai_modules/person/pages/task_flow/task_flow",
        type: "redirect",
        params: { id: personId.value, templateId: tplId },
    });
};

// ─── 套用模板 ──────────────────────────────────────────────────
const handleSwitchTemplate = async (tplId: string) => {
    if (tplId != currentTemplateId.value) {
        await taskStore.switchTemplate(tplId);
    }
    uni.$u.route({
        url: "/ai_modules/person/pages/task_flow/task_flow",
        type: "redirect",
        params: { id: personId.value },
    });
};

// ─── 删除模板 ──────────────────────────────────────────────────
const handleDeleteTemplate = (id: string, name: string) => {
    uni.showModal({
        title: "删除模板",
        content: `确定删除「${name}」吗？删除后无法恢复。`,
        confirmColor: "#EF4444",
        success: async ({ confirm }) => {
            if (!confirm) return;
            uni.showLoading({ title: "删除中...", mask: true });
            try {
                await taskStore.deleteTemplate(id);
                templateList.value = templateList.value.filter((t) => t.id !== id);
                uni.hideLoading();
                uni.showToast({ title: "已删除", icon: "none", duration: 3000 });
            } catch (error: any) {
                uni.hideLoading();
                uni.showToast({ title: error || "删除失败，请重试", icon: "none" });
            }
        },
    });
};

// ─── 新建自定义模板 ────────────────────────────────────────────
const openCreateTplModal = () => {
    newTplName.value = "";
    createTplError.value = "";
    showCreateTplModal.value = true;
};

const closeCreateTplModal = () => {
    showCreateTplModal.value = false;
};

const handleCreateTemplate = async () => {
    const name = newTplName.value.trim();
    if (!name) {
        createTplError.value = "模板名称不能为空";
        return;
    }
    isCreating.value = true;
    uni.showLoading({ title: "创建中...", mask: true });
    try {
        await taskStore.createTemplate(name);
        closeCreateTplModal();
        uni.hideLoading();
        uni.showToast({ title: "创建成功", icon: "none", duration: 3000 });
        pagingRef.value?.reload();
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error || "创建失败，请重试", icon: "none" });
    } finally {
        isCreating.value = false;
    }
};

const back = () => {
    // 获取当前页面栈，判断「上一个页面」是否为 task_flow
    const pages = getCurrentPages();
    const prevPage = pages[pages.length - 2];
    if (prevPage?.route === "/ai_modules/person/pages/task_flow/task_flow") {
        uni.navigateBack();
    } else {
        uni.redirectTo({
            url: `/ai_modules/person/pages/task_flow/task_flow?id=${personId.value}`,
        });
    }
};

// ─── 生命周期 ──────────────────────────────────────────────────
onLoad((options: any) => {
    personId.value = options?.id ?? "";
    taskStore.init(Number(personId.value));
    fetchCategoryList();
});
</script>
