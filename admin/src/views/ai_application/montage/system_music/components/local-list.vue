<template>
    <div>
        <el-alert
            type="info"
            :closable="false"
            show-icon
            title="本站上传的曲目只存在本站曲库，不会同步到模板中心。开启「前台显示」后，用户端手动选曲、人设配乐与自动出片都能选到；关闭后立即从用户端消失（不会删除文件）。上传时可选分组，不选则归入「无分组」，用户端只在「全部」里出现。支持 mp3 / wav / m4a / aac / flac / ogg。"
            class="mb-4" />

        <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
            <el-form-item label="名称">
                <el-input
                    class="w-[220px]"
                    v-model="queryParams.name"
                    placeholder="请输入曲目名称"
                    clearable
                    @keyup.enter="resetPage" />
            </el-form-item>
            <el-form-item label="状态">
                <el-select
                    class="!w-[140px]"
                    v-model="queryParams.status"
                    placeholder="全部"
                    clearable
                    :empty-values="[undefined, null]"
                    @change="resetPage">
                    <el-option label="全部" value="" />
                    <el-option label="前台显示" value="1" />
                    <el-option label="已隐藏" value="0" />
                </el-select>
            </el-form-item>
            <el-form-item label="分组">
                <el-select
                    class="!w-[160px]"
                    v-model="queryParams.group_id"
                    placeholder="全部分组"
                    clearable
                    :empty-values="[undefined, null]"
                    @change="resetPage">
                    <el-option label="全部分组" value="" />
                    <el-option label="无分组" :value="0" />
                    <el-option v-for="item in groupOptions" :key="item.id" :label="item.name" :value="item.id" />
                </el-select>
            </el-form-item>
            <el-form-item>
                <el-button type="primary" @click="resetPage">查询</el-button>
                <el-button @click="resetParams">重置</el-button>
            </el-form-item>
            <el-form-item class="ml-auto">
                <el-button v-perms="['shanjian.systemMusic/upload']" type="primary" @click="openUpload">
                    上传音乐
                </el-button>
            </el-form-item>
        </el-form>

        <el-table class="mt-4" size="large" v-loading="pager.loading" :data="pager.lists" row-key="id">
            <el-table-column label="名称" prop="name" min-width="180" show-overflow-tooltip />
            <el-table-column label="试听" min-width="240">
                <template #default="{ row }">
                    <audio v-if="row.audio_url" :src="row.audio_url" controls preload="none" class="w-full h-9" />
                    <span v-else class="text-tx-secondary">-</span>
                </template>
            </el-table-column>
            <el-table-column label="时长" width="90" align="center">
                <template #default="{ row }">{{ formatDuration(row.duration_sec) }}</template>
            </el-table-column>
            <el-table-column label="分组" min-width="150">
                <template #default="{ row }">
                    <el-select
                        class="!w-[140px]"
                        :model-value="row.group_id"
                        :disabled="!hasAuth(['shanjian.systemMusic/changeGroup'])"
                        @change="(val) => handleGroup(row, val as number)">
                        <el-option label="无分组" :value="0" />
                        <el-option v-for="item in groupOptions" :key="item.id" :label="item.name" :value="item.id" />
                    </el-select>
                </template>
            </el-table-column>
            <el-table-column label="本站排序" width="170">
                <template #default="{ row }">
                    <div class="flex items-center gap-1">
                        <el-input-number
                            class="!w-[90px]"
                            :controls="false"
                            :min="-99999"
                            :max="99999"
                            v-model="row.sort_override"
                            placeholder="默认"
                            @change="(val) => handleSort(row, val)" />
                        <el-button
                            v-if="row.sort_override !== null && row.sort_override !== undefined"
                            type="primary"
                            link
                            @click="handleResetSort(row)">
                            重置
                        </el-button>
                    </div>
                </template>
            </el-table-column>
            <el-table-column label="前台显示" min-width="90">
                <template #default="{ row }">
                    <el-switch
                        v-perms="['shanjian.systemMusic/changeStatus']"
                        :model-value="row.status === 1"
                        @change="handleStatus(row)" />
                </template>
            </el-table-column>
            <el-table-column label="上传时间" min-width="170">
                <template #default="{ row }">{{ row.create_time || "-" }}</template>
            </el-table-column>
            <el-table-column label="操作" width="140" fixed="right">
                <template #default="{ row }">
                    <el-button v-perms="['shanjian.systemMusic/save']" type="primary" link @click="openRename(row)">
                        编辑名称
                    </el-button>
                    <el-button v-perms="['shanjian.systemMusic/delete']" type="danger" link @click="handleDelete(row)">
                        删除
                    </el-button>
                </template>
            </el-table-column>
        </el-table>
        <div class="flex justify-end mt-4">
            <pagination v-model="pager" @change="getLists" />
        </div>

        <!-- 上传曲目 -->
        <el-dialog
            v-model="uploadVisible"
            title="上传音乐"
            width="560px"
            :close-on-click-modal="false"
            @closed="resetUpload">
            <el-form label-width="90px">
                <el-form-item label="音频文件" required>
                    <el-upload
                        class="w-full"
                        drag
                        :accept="MUSIC_ACCEPT"
                        :show-file-list="false"
                        :disabled="uploading"
                        :http-request="handleUpload">
                        <div v-if="uploading" class="py-4 text-tx-secondary">上传中，请稍候…</div>
                        <template v-else-if="form.audio_url">
                            <div class="py-2">已上传：{{ uploadedFileName || "音频文件" }}</div>
                            <div class="text-xs text-tx-secondary">点击或拖拽可重新选择</div>
                        </template>
                        <template v-else>
                            <div class="py-2">点击或拖拽音频到此处</div>
                            <div class="text-xs text-tx-secondary">支持 {{ MUSIC_ALLOWED_EXTS.join(" / ") }}，单文件不超过 20MB</div>
                        </template>
                    </el-upload>
                </el-form-item>
                <el-form-item label="曲目名称" required>
                    <el-input v-model="form.name" maxlength="128" show-word-limit placeholder="留空自动取文件名" />
                </el-form-item>
                <el-form-item label="分组">
                    <el-select v-model="form.group_id" class="w-full" placeholder="不选则归入「无分组」">
                        <el-option label="无分组" :value="0" />
                        <el-option v-for="item in groupOptions" :key="item.id" :label="item.name" :value="item.id" />
                    </el-select>
                </el-form-item>
                <el-form-item label="时长">
                    <span>{{ formatDuration(form.duration_sec) }}</span>
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="uploadVisible = false">取消</el-button>
                <el-button
                    v-perms="['shanjian.systemMusic/save']"
                    type="primary"
                    :loading="saving"
                    :disabled="uploading"
                    @click="handleSave">
                    确定
                </el-button>
            </template>
        </el-dialog>

        <!-- 编辑名称 -->
        <el-dialog v-model="renameVisible" title="编辑曲目名称" width="460px" :close-on-click-modal="false">
            <el-form label-width="90px">
                <el-form-item label="曲目名称" required>
                    <el-input v-model="renameForm.name" maxlength="128" show-word-limit placeholder="请输入曲目名称" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="renameVisible = false">取消</el-button>
                <el-button type="primary" :loading="saving" @click="handleRename">确定</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script lang="ts" setup>
import type { UploadRequestOptions } from "element-plus";
import {
    changeSystemMusicGroup,
    changeSystemMusicStatus,
    deleteSystemMusic,
    getSystemMusicLists,
    saveSystemMusic,
    updateSystemMusicSort,
    uploadSystemMusic,
} from "@/api/ai_application/digital_human/system_music";
import feedback from "@/utils/feedback";
import { usePaging } from "@/hooks/usePaging";
import { useAuth } from "@/hooks/useAuth";
import { formatDuration, loadMusicGroups, MUSIC_ACCEPT, MUSIC_ALLOWED_EXTS, MUSIC_MAX_SIZE, readAudioDuration } from "../shared";

const { hasAuth } = useAuth();

const queryParams = reactive({
    name: "",
    status: "",
    // 分组筛选：空=全部，0=无分组；只作用于当前 tab 的来源
    group_id: "" as number | "",
    // 只看站长自己上传的曲目（模板中心同步的在另一个 tab）
    source: "local",
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getSystemMusicLists,
    params: queryParams,
});

// 分组下拉：两个 tab 都要用，列表页与上传弹窗共用同一份
const groupOptions = ref<Array<{ id: number; name: string }>>([]);
loadMusicGroups().then((list) => {
    groupOptions.value = list;
});

const uploading = ref(false);
const saving = ref(false);
const uploadVisible = ref(false);
const uploadedFileName = ref("");
const form = reactive({
    audio_url: "",
    name: "",
    duration_sec: 0,
    group_id: 0,
});

const renameVisible = ref(false);
const renameForm = reactive({ id: "", name: "" });

const resetUpload = () => {
    uploading.value = false;
    uploadedFileName.value = "";
    form.audio_url = "";
    form.name = "";
    form.duration_sec = 0;
    form.group_id = 0;
};

const openUpload = () => {
    resetUpload();
    uploadVisible.value = true;
};

/** 选择文件即上传（拿到地址后才能提交曲目信息），失败时保留弹窗让站长重选 */
const handleUpload = async (options: UploadRequestOptions) => {
    const file = options.file as File;
    const ext = (file.name.split(".").pop() || "").toLowerCase();
    if (!MUSIC_ALLOWED_EXTS.includes(ext)) {
        feedback.msgError(`仅支持 ${MUSIC_ALLOWED_EXTS.join(" / ")} 格式的音频`);
        return;
    }
    if (file.size > MUSIC_MAX_SIZE) {
        feedback.msgError("音频文件不能超过 20MB");
        return;
    }

    uploading.value = true;
    try {
        const res: any = await uploadSystemMusic(file);
        form.audio_url = res?.audio_url || "";
        uploadedFileName.value = file.name;
        if (!form.name) {
            form.name = file.name.replace(/\.[^.]+$/, "").slice(0, 128);
        }
        form.duration_sec = form.audio_url ? await readAudioDuration(form.audio_url) : 0;
    } catch {
        // 接口层已提示具体原因，这里只保证弹窗回到可选文件状态
        form.audio_url = "";
        uploadedFileName.value = "";
    } finally {
        uploading.value = false;
    }
};

const handleSave = async () => {
    if (!form.audio_url) {
        feedback.msgError("请先上传音频文件");
        return;
    }
    if (!form.name.trim()) {
        feedback.msgError("请输入曲目名称");
        return;
    }
    saving.value = true;
    try {
        await saveSystemMusic({
            name: form.name.trim(),
            audio_url: form.audio_url,
            duration_sec: form.duration_sec,
            group_id: form.group_id,
        });
        feedback.msgSuccess("添加成功");
        uploadVisible.value = false;
        resetPage();
    } finally {
        saving.value = false;
    }
};

const openRename = (row: any) => {
    renameForm.id = row.id;
    renameForm.name = row.name;
    renameVisible.value = true;
};

const handleRename = async () => {
    if (!renameForm.name.trim()) {
        feedback.msgError("请输入曲目名称");
        return;
    }
    saving.value = true;
    try {
        await saveSystemMusic({ id: renameForm.id, name: renameForm.name.trim() });
        feedback.msgSuccess("保存成功");
        renameVisible.value = false;
        getLists();
    } finally {
        saving.value = false;
    }
};

const handleDelete = async (row: any) => {
    await feedback.confirm(`确定删除「${row.name}」吗？删除后用户端立即不可用，且无法恢复（已生成的作品不受影响）。`);
    await deleteSystemMusic({ id: row.id });
    feedback.msgSuccess("删除成功");
    // 删掉当前页最后一条时回到上一页更符合预期
    if (pager.lists.length === 1 && pager.page > 1) {
        pager.page -= 1;
    }
    getLists();
};

const handleStatus = async (row: any) => {
    const next = row.status === 1 ? 0 : 1;
    if (next === 0) {
        await feedback.confirm(`确定隐藏「${row.name}」吗？隐藏后用户端选曲与随机配乐都不再使用该曲目。`);
    }
    await changeSystemMusicStatus({ id: row.id, status: next });
    getLists();
};

const handleSort = async (row: any, val: number | undefined) => {
    if (val === undefined || val === null) return;
    await updateSystemMusicSort({ id: row.id, sort: val });
    feedback.msgSuccess("排序已更新");
    getLists();
};

const handleResetSort = async (row: any) => {
    await updateSystemMusicSort({ id: row.id, sort: "" });
    row.sort_override = null;
    feedback.msgSuccess("已恢复默认排序");
    getLists();
};

// 行内改分组：0=无分组。成功后重新拉列表，保证排序与筛选项一致
const handleGroup = async (row: any, val: number) => {
    if (row.group_id === val) return;
    await changeSystemMusicGroup({ id: row.id, group_id: val });
    feedback.msgSuccess("分组已更新");
    getLists();
};

getLists();
</script>
