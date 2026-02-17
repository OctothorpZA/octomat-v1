export interface SharedData {
    auth: {
        user: import('./auth').User;
        roles: string[];
    };
    navigation?: import('./nav').NavigationItem[];
}
