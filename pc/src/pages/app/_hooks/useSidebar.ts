export interface UpdateSliderIndexParams {
    type: number;
    [key: string]: any;
}

export interface SidebarItem {
    type: number;
    components: any;
    icon?: string;
    name: string;
    disabled?: boolean;
}

export default function useSidebar() {
    const router = useRouter();
    const route = useRoute();

    const routerParams = ref<Record<string, any>>({});
    const residentParams = ref<Record<string, any>>({});

    const sidebar = ref<Array<SidebarItem>>([]);
    const sidebarIndex = ref<number>(0);

    const getComponents = computed(() => {
        const index = sidebar.value.findIndex((item) => item.type == sidebarIndex.value);
        return sidebar.value[index]?.components;
    });

    const setSlider = (lists: any[]) => {
        sidebar.value = lists;
    };

    const getSliderIndex = (index: number) => {
        sidebarIndex.value = index;
        routerParams.value = {};
        pushHistory();
    };

    const updateSliderIndex = (params: UpdateSliderIndexParams) => {
        sidebarIndex.value = Number(params.type);
        routerParams.value = params;
        pushHistory();
    };

    const pushHistory = () => {
        router.replace({
            query: {
                ...routerParams.value,
                ...residentParams.value,
                type: sidebarIndex.value,
            },
        });
    };

    const init = async () => {
        await nextTick();
        const type = Number(route.query.type);
        if (type == -1 || !type || sidebar.value.every((item) => item.type != type)) {
            sidebarIndex.value = sidebar.value[0].type;
        } else {
            sidebarIndex.value = type;
        }
        routerParams.value = route.query;
        pushHistory();
    };

    init();

    return {
        sidebar,
        sidebarIndex,
        getComponents,
        routerParams,
        residentParams,
        init,
        setSlider,
        getSliderIndex,
        updateSliderIndex,
        pushHistory,
    };
}
