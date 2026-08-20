<template>
    <div v-loading="loading">
        <el-card class="!border-none" shadow="never">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="inline-block w-1 h-[18px] rounded" style="background: var(--el-color-primary)" />
                    <span class="text-lg font-medium">底部导航设置</span>
                    <span class="text-sm text-tx-secondary">
                        至少添加 {{ MIN }} 个导航，最多添加 {{ MAX }} 个导航
                    </span>
                </div>
                <el-button type="primary" :loading="saving" v-perms="['decorate.tabbar/save']" @click="handleSave">
                    保存
                </el-button>
            </div>

            <el-tabs v-model="activeTab" class="mt-4">
                <!-- 导航图片 -->
                <el-tab-pane label="导航图片" name="image">
                    <div class="flex flex-col gap-4">
                        <div v-for="(item, index) in navList" :key="index" class="relative rounded p-4 bg-[#f8f9fa]">
                            <el-button
                                v-if="navList.length > MIN"
                                class="!absolute right-3 top-3"
                                type="danger"
                                link
                                @click="handleDelete(index)">
                                删除
                            </el-button>

                            <div class="flex items-start mb-4">
                                <div class="form-label">导航图标</div>
                                <div class="flex gap-3">
                                    <material-picker v-model="item.unselected" :limit="1" />
                                    <material-picker v-model="item.selected" :limit="1" />
                                </div>
                            </div>

                            <div class="flex items-center mb-4">
                                <div class="form-label">导航名称</div>
                                <el-input
                                    v-model="item.name"
                                    class="!w-[420px]"
                                    placeholder="请输入导航名称"
                                    :maxlength="8"
                                    clearable />
                            </div>

                            <div class="flex items-center">
                                <div class="form-label">链接地址</div>
                                <div class="flex items-center gap-2">
                                    <div class="w-[420px] cursor-pointer" @click="openLink(index)">
                                        <el-input :model-value="item.link?.name" readonly placeholder="请选择链接" />
                                    </div>
                                    <el-button v-if="item.link" link type="info" @click="clearLink(index)">
                                        清除
                                    </el-button>
                                </div>
                            </div>
                        </div>

                        <div>
                            <el-button v-if="navList.length < MAX" type="primary" plain @click="handleAdd">
                                添加导航
                            </el-button>
                        </div>
                    </div>
                </el-tab-pane>

                <!-- 样式设置 -->
                <el-tab-pane label="样式设置" name="style">
                    <el-form label-width="110px" class="mt-2">
                        <el-form-item label="默认文字颜色">
                            <el-color-picker v-model="style.default_color" />
                        </el-form-item>
                        <el-form-item label="选中文字颜色">
                            <el-color-picker v-model="style.selected_color" />
                        </el-form-item>
                    </el-form>
                </el-tab-pane>
            </el-tabs>
        </el-card>

        <link-picker ref="linkPickerRef" @confirm="handleLinkConfirm" />
    </div>
</template>
<script lang="ts" setup>
import { getTabbarDetail, saveTabbar } from "@/api/decoration/tabbar";
import feedback from "@/utils/feedback";
import LinkPicker from "./link-picker.vue";

const MIN = 2;
const MAX = 5;

const activeTab = ref("image");

const createItem = () => ({
    name: "",
    unselected: "",
    selected: "",
    link: null as any,
    is_show: 1,
});

const navList = ref<any[]>([createItem(), createItem()]);
const style = reactive({
    default_color: "#999999",
    selected_color: "#0065FB",
});

// 加载详情
const loading = ref(false);
const getDetail = async () => {
    loading.value = true;
    try {
        const data: any = await getTabbarDetail();
        if (data?.list?.length) {
            navList.value = data.list.map((item: any) => ({
                ...createItem(),
                ...item,
            }));
        }
        if (data?.style) {
            Object.assign(style, data.style);
        }
    } finally {
        loading.value = false;
    }
};
getDetail();

const handleAdd = () => {
    if (navList.value.length >= MAX) return;
    navList.value.push(createItem());
};

const handleDelete = (index: number) => {
    if (navList.value.length <= MIN) return;
    navList.value.splice(index, 1);
};

// 链接选择
const linkPickerRef = shallowRef<InstanceType<typeof LinkPicker>>();
const currentIndex = ref(0);

const openLink = (index: number) => {
    currentIndex.value = index;
    linkPickerRef.value?.open(navList.value[index].link);
};

const handleLinkConfirm = (link: any) => {
    navList.value[currentIndex.value].link = link;
};

const clearLink = (index: number) => {
    navList.value[index].link = null;
};

// 保存
const saving = ref(false);

const handleSave = async () => {
    if (navList.value.length < MIN) {
        return feedback.msgError(`至少添加 ${MIN} 个导航`);
    }
    for (let i = 0; i < navList.value.length; i++) {
        const item = navList.value[i];
        if (!item.unselected || !item.selected) {
            return feedback.msgError(`请上传第 ${i + 1} 个导航的图标`);
        }
        if (!item.name) {
            return feedback.msgError(`请输入第 ${i + 1} 个导航的名称`);
        }
        if (!item.link) {
            return feedback.msgError(`请选择第 ${i + 1} 个导航的链接地址`);
        }
    }

    saving.value = true;
    try {
        await saveTabbar({ list: navList.value, style });
        await getDetail();
    } finally {
        saving.value = false;
    }
};
</script>
<style scoped>
.form-label {
    width: 90px;
    flex: none;
    line-height: 32px;
    color: var(--el-text-color-regular);
}
</style>
