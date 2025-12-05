<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="名称">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.name"
                        placeholder="请输入名称"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="风格">
                    <el-select
                        v-model="queryParams.style"
                        class="!w-[160px]"
                        placeholder="请选择风格"
                        clearable
                        :empty-values="[null, undefined]"
                        @change="getLists()">
                        <el-option label="全部" value="" />
                        <el-option label="科技" value="1" />
                        <el-option label="悬疑" value="2" />
                        <el-option label="抒情" value="3" />
                        <el-option label="欢快" value="4" />
                        <el-option label="古典" value="5" />
                        <el-option label="跳跃" value="6" />
                    </el-select>
                </el-form-item>
                <el-form-item label="创建时间">
                    <daterange-picker
                        v-model:startTime="queryParams.start_time"
                        v-model:endTime="queryParams.end_time" />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>
        <el-card class="!border-none mt-4" shadow="never">
            <div class="mb-4 flex justify-between">
                <el-button
                    v-perms="['ai_setting.bg_music/delete']"
                    type="default"
                    :plain="true"
                    :disabled="!multipleSelection.length"
                    @click="handleDelete(multipleSelection.map((item) => item.id))">
                    批量删除
                </el-button>
                <el-button v-perms="['ai_setting.bg_music/add']" type="primary" @click="handleAdd">
                    <template #icon>
                        <icon name="el-icon-Plus" />
                    </template>
                    新增
                </el-button>
            </div>
            <el-table
                ref="tableRef"
                size="large"
                v-loading="pager.loading"
                row-key="id"
                :data="pager.lists"
                @selection-change="handleSelectionChange">
                <el-table-column type="selection" width="55" fixed="left" reserve-selection />
                <el-table-column label="ID" prop="id" min-width="80" fixed="left" />
                <el-table-column label="名称" prop="name" min-width="100" />
                <el-table-column label="风格" prop="style_text" min-width="120" />
                <el-table-column label="创作时间" prop="create_time" min-width="180" />
                <el-table-column label="操作" width="160" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="handlePlay(row)">
                            {{ isPlaying && currAudioId === row.id ? "暂停" : "播放" }}
                        </el-button>
                        <el-button v-perms="['ai_setting.bg_music/edit']" type="primary" link @click="handleEdit(row)">
                            编辑
                        </el-button>
                        <el-button
                            v-perms="['ai_setting.bg_music/delete']"
                            type="danger"
                            link
                            @click="handleDelete([row.id])">
                            删除
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>
    </div>
    <edit-popup v-if="showEdit" ref="editRef" @success="getLists" @close="showEdit = false" />
</template>
<script lang="ts" setup>
import { getMaterialMusicList, deleteMaterialMusic } from "@/api/ai_setting/music";
import { usePaging } from "@/hooks/usePaging";
import feedback from "@/utils/feedback";
import { ElTable } from "element-plus";
import EditPopup from "./edit.vue";
import { useAudio } from "@/hooks/useAudioPlay";
const queryParams = reactive({
    name: "",
    start_time: "",
    end_time: "",
    style: "",
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getMaterialMusicList,
    params: queryParams,
});

const tableRef = ref<InstanceType<typeof ElTable>>();

//弹框ref
const editRef = shallowRef<InstanceType<typeof EditPopup>>();
const showEdit = ref(false);

const multipleSelection = ref<any[]>([]);

const handleSelectionChange = (val: any[]) => {
    multipleSelection.value = val;
};

//打开弹框
const handleAdd = async () => {
    showEdit.value = true;
    await nextTick();
    editRef.value?.open("add");
};

const handleEdit = async (row: any) => {
    showEdit.value = true;
    await nextTick();
    editRef.value?.open("edit");
    editRef.value?.setFormData(row);
};

const handleDelete = async (id: number | number[]) => {
    await feedback.confirm("确定要删除吗？");
    await deleteMaterialMusic({ id });
    getLists();
    multipleSelection.value = [];
    tableRef.value?.clearSelection();
};

const { play, pause, pauseAll, setUrl, isPlaying } = useAudio();
const currAudioId = ref<number>();

const handlePlay = (row: any) => {
    const { id, url } = row;
    if (isPlaying.value && currAudioId.value !== id) {
        pauseAll();
    }

    if (!isPlaying.value) {
        if (currAudioId.value !== id) {
            setUrl(url);
        }
        play();
        currAudioId.value = id;
    } else {
        pause();
    }
};

getLists();
</script>
