<template>
	<div>
		<el-card shadow="never" class="!border-none mt-4">
			<el-form
				ref="formRef"
				class="mb-[-16px]"
				:model="queryParams"
				:inline="true">
				<el-form-item label="风格名称">
					<el-input
						class="w-[280px]"
						v-model="queryParams.name"
						placeholder="请输入风格名称"
						clearable
						@keyup.enter="resetPage" />
				</el-form-item>
				<el-form-item label="风格状态">
					<el-select class="!w-[280px]" v-model="queryParams.status">
						<el-option label="全部" value="" />
						<el-option label="开启" value="1" />
						<el-option label="关闭" value="0" />
					</el-select>
				</el-form-item>
				<el-form-item>
					<el-button type="primary" @click="resetPage"
						>查询</el-button
					>
					<el-button @click="resetParams">重置</el-button>
				</el-form-item>
			</el-form>
		</el-card>
		<el-card class="!border-none mt-4" shadow="never">
			<el-button
				v-perms="['music.musicStyle/add']"
				type="primary"
				class="mb-4"
				@click="handleAdd">
				+ 新增
			</el-button>
			<el-button
				v-perms="['music.musicStyle/del']"
				class="mb-4"
				@click="handleDelete(selectData)"
				:disabled="!selectData.length">
				批量删除
			</el-button>
			<el-table
				size="large"
				v-loading="pager.loading"
				:data="pager.lists"
				@selection-change="handleSelectionChange">
				<el-table-column type="selection" width="55" fixed="left" />
				<el-table-column label="封面" width="100">
					<template #default="{ row }">
						<ImageContain
							class="flex-none"
							v-if="row.image"
							:src="row.image"
							:width="48"
							:height="48"
							:preview-src-list="[row.image]"
							preview-teleported
							fit="contain" />
					</template>
				</el-table-column>
				<el-table-column label="风格名称" prop="name" min-width="120" />
				<el-table-column
					label="已被关联"
					min-width="100"
					prop="relation_count" />
				<el-table-column label="排序" prop="sort" min-width="100" />
				<el-table-column label="状态" min-width="100">
					<template #default="{ row }">
						<el-switch
							@change="changeStatus(row.id)"
							v-model="row.status"
							:active-value="1"
							:inactive-value="0" />
					</template>
				</el-table-column>
				<el-table-column
					label="创建时间"
					prop="create_time"
					sortable
					min-width="100" />
				<el-table-column label="操作" width="150" fixed="right">
					<template #default="{ row }">
						<el-button
							v-perms="['music.musicStyle/edit']"
							type="primary"
							link
							@click="handleEdit(row)">
							编辑
						</el-button>
						<el-button
							v-perms="['music.musicStyle/del']"
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
		<Edit
			v-if="showEdit"
			ref="editRef"
			@success="getLists"
			@close="showEdit = false" />
	</div>
</template>
<script setup lang="ts">
import { usePaging } from "@/hooks/usePaging";
import Edit from "./edit.vue";
import {
	getMusicStyle,
	deleteMusicStyle,
	putMusicStyleStatus,
} from "@/api/ai_application/music";
import feedback from "@/utils/feedback";

const editRef = shallowRef<InstanceType<typeof Edit>>();
//搜索参数
const queryParams = reactive({
	name: "",
	status: "",
});
const showEdit = ref(false);
const selectData = ref<any[]>([]);
const handleSelectionChange = (val: any[]) => {
	selectData.value = val.map((item) => item.id);
};
//添加
const handleAdd = async () => {
	showEdit.value = true;
	await nextTick();
	editRef.value?.open("add");
};
//编辑
const handleEdit = async (data: any) => {
	showEdit.value = true;
	await nextTick();
	editRef.value?.open("edit");
	editRef.value?.setFormData(data);
};
//删除
const handleDelete = async (id: number[]) => {
	await feedback.confirm("确定要删除？");
	await deleteMusicStyle({ id });
	getLists();
};

//修改状态
const changeStatus = async (id: any) => {
	try {
		await putMusicStyleStatus({ id });
	} finally {
		getLists();
	}
};

const { pager, getLists, resetPage, resetParams } = usePaging({
	fetchFun: getMusicStyle,
	params: queryParams,
});

getLists();
</script>
