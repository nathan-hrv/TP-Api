package fr.esgi.todo.security;

/** Rôles des utilisateurs, transportés dans le claim "groups" du JWT et lus par @RolesAllowed. */
public final class Roles {

    public static final String ADMIN = "admin";
    public static final String USER = "user";

    private Roles() {
    }
}
