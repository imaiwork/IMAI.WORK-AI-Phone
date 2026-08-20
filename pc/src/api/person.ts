export const getPersonList = (params?: Record<string, any>) => {
    return $request.get({ url: "/aiPersona.aiPersona/lists", params });
};
