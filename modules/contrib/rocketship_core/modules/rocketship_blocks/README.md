
Has a new overview for Content Blocks, accessible by webadmins. It's a View, 
so change away after installation. This one is placed at admin/content. 
There's also a redirect from the original library at admin/structure because 
that one is still the normal redirect after an edit or translate action. The 
original path is a lot harder to tweak, as it is both a route (backup) and a 
View, and altering both seems a bit much but we may do that in the future.

Custom blocks:

* copyright block
    * Outputs this year and Dropsolid
* legal block, block config has two links one for terms of use and one for 
privacy policy
    * will eventually probably be upgraded/integrated with entity_legal or 
    something more robust
* SearchRedirectBlock:
    * Exposes a simple input with search and reset buttons.
    * Reset is optional
    * Can select where to redirect to. Has support for <current> token.
    * Can select what query parameter the input is attached to (what the user
     entered)
    * Useful for search. Can, for example, redirect to 
    /search?search-key=user-input
