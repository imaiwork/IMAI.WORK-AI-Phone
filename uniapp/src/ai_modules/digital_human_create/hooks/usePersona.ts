interface UsePersonaOptions {
    formData: any;
}

export function usePersona({ formData }: UsePersonaOptions) {
    const showCharacter = ref(false);
    const isCharacter = ref(false);

    // ─── 从历史人设中选择 ────────────────────────────────────────

    const handleSelectCharacter = (item: any): void => {
        formData.person_name = item.name;
        formData.person_introduction = item.introduced;
        isCharacter.value = true;
        showCharacter.value = false;
    };

    return {
        showCharacter,
        isCharacter,
        handleSelectCharacter,
    };
}
