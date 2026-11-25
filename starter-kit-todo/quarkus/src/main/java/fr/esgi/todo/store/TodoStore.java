package fr.esgi.todo.store;

import fr.esgi.todo.model.Todo;
import jakarta.enterprise.context.ApplicationScoped;
import java.util.List;
import java.util.Optional;

/**
 * Stockage des tâches EN MÉMOIRE : aucune base de données.
 *
 * Conséquences à comprendre (voir le cours, section 3.3) :
 *  - tout est perdu quand l'application s'arrête ;
 *  - une seule instance de l'API peut tourner (deux instances auraient chacune leurs données) ;
 *  - plusieurs requêtes arrivent EN MÊME TEMPS : les structures utilisées doivent être thread-safe.
 *
 * TP 3, étape 3 : compléter les trois méthodes. Tant qu'elles ne sont pas écrites, l'API répond 500.
 */
@ApplicationScoped
public class TodoStore {

    // TP 3, étape 3 : déclarer ici
    //   - la map qui contient les tâches (clé : identifiant) ;
    //   - le compteur qui attribue les identifiants.

    /** Crée une tâche (id attribué par le serveur, done à false, createdAt à l'instant présent). */
    public Todo create(String title) {
        throw new UnsupportedOperationException("À écrire (TP 3, étape 3)");
    }

    /** Toutes les tâches, dans l'ordre de leur identifiant. */
    public List<Todo> findAll() {
        throw new UnsupportedOperationException("À écrire (TP 3, étape 3)");
    }

    /** Une tâche, ou rien si l'identifiant est inconnu. */
    public Optional<Todo> findById(int id) {
        throw new UnsupportedOperationException("À écrire (TP 3, étape 3)");
    }
}
