import { todoLists, deleteTodo } from "@/api/person_wechat";

export default function useTodo({ isScroll = true }: { isScroll?: boolean } = {}) {
    const nuxtApp = useNuxtApp();

    const todoParams = reactive({
        page_size: 10,
        page_no: 1,
        wechat_id: "",
        friend_id: "",
    });

    const {
        pager: todoPager,
        getLists: getTodoLists,
        resetPage: resetTodoPage,
        resetParams: resetTodoParams,
    } = usePaging({
        fetchFun: todoLists,
        params: todoParams,
        isScroll,
    });

    const handleDeleteTodo = async (id: number) => {
        return new Promise(async (resolve, reject) => {
            await nuxtApp.$confirm({
                message: "确定要删除吗？",
                onConfirm: async () => {
                    try {
                        await deleteTodo({ id });
                        feedback.msgSuccess("删除成功");
                        resolve(true);
                        const index = todoPager.lists.findIndex((item) => item.id === id);
                        if (index !== -1) {
                            todoPager.lists.splice(index, 1);
                        }
                        // resetTodoPage();
                    } catch (error) {
                        feedback.msgError(error || "删除失败");
                        reject(false);
                    }
                },
            });
        });
    };

    return {
        todoPager,
        todoParams,
        getTodoLists,
        resetTodoPage,
        resetTodoParams,
        handleDeleteTodo,
    };
}
