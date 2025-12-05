<template>
	<div>
		<el-card class="!border-none" shadow="never">
			<el-form
				ref="formRef"
				class="mb-[-16px]"
				:model="queryParams"
				:inline="true">
				<el-form-item label="分类名称">
					<el-input
						class="w-[200px]"
						v-model="queryParams.title"
						placeholder="请输入分类名称"
						clearable
						@keyup.enter="getLists" />
				</el-form-item>
				<el-form-item>
					<el-button type="primary" @click="getLists">查询</el-button>
					<el-button @click="resetPage">重置</el-button>
				</el-form-item>
			</el-form>
		</el-card>
		<el-card class="!border-none mt-4" shadow="never">
			<div>
				<el-button
					v-perms="['ai_application.sd.prompt_category/add']"
					type="primary"
					@click="openPop('add')">
					<template #icon>
						<icon name="el-icon-Plus" />
					</template>
					新增
				</el-button>
			</div>

			<el-table
				size="large"
				class="mt-4"
				v-loading="pager.loading"
				:data="pager.lists">
				<el-table-column label="名称" prop="title"></el-table-column>
				<el-table-column
					label="状态"
					min-width="100"
					v-perms="['ai_application.sd.prompt_category/status']">
					<template #default="{ row }">
						<el-switch
							@change="changeStatus(row)"
							v-model="row.status"
							:active-value="1"
							:inactive-value="0" />
					</template>
				</el-table-column>
				<el-table-column label="排序" prop="sort" min-width="120" />
				<el-table-column
					label="创建时间"
					prop="create_time"
					show-overflow-tooltip
					min-width="180" />
				<el-table-column label="操作" width="150" fixed="right">
					<template #default="{ row }">
						<el-button
							v-perms="['ai_application.sd.prompt_category/edit']"
							type="primary"
							link
							@click="openPop('edit', row)">
							编辑
						</el-button>
						<el-button
							v-perms="[
								'ai_application.sd.prompt_category/delete',
							]"
							type="danger"
							link
							@click="handleDelete(row.id, row.sample_count)">
							删除
						</el-button>
					</template>
				</el-table-column>
			</el-table>
			<div class="flex justify-end mt-4">
				<pagination v-model="pager" @change="getLists" />
			</div>
		</el-card>
		<edit-popup v-if="showEdit" ref="editRef" @success="getLists" />
	</div>
</template>
<script lang="tsx" setup name="drawCategory">
import EditPopup from "./edit.vue";
import {
	getDrawCategoryList,
	delDrawCategory,
	editDrawCategoryStatus,
} from "@/api/ai_application/draw/draw_sd";
import feedback from "@/utils/feedback";
import { usePaging } from "@/hooks/usePaging";

//弹框ref
const editRef = shallowRef<InstanceType<typeof EditPopup>>();
//搜索参数
const queryParams = reactive({
	title: "",
});

const { pager, getLists, resetPage, resetParams } = usePaging({
	fetchFun: getDrawCategoryList,
	params: queryParams,
});

//是/否显示编辑弹框
const showEdit = ref(true);

//打开弹框
const openPop = (type: string, value: any = {}) => {
	editRef.value?.open(type, value);
};

//删除
const handleDelete = async (id: number, sample_count: number) => {
	await feedback.confirm("确定要删除吗？");
	await delDrawCategory({ id });
	getLists();
};

//修改状态
const changeStatus = async (row: any) => {
	try {
		await editDrawCategoryStatus({ id: row.id, status: row.status });
	} finally {
		getLists();
	}
};

getLists();
</script>
