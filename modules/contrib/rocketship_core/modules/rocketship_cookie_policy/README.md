Contains a cookie banner. Users can set a node as the "read more" link and 
enter the company name.

Also contains a demo submodule which will migrate a Cookie Policy node. 
Replaces general tokens with their value on migrate, such as [site:name].


The submodule migrates a Cookie Policy, Privacy Policy and Legal Disclaimer 
page. These are just standard "Page" nodes, but during the migrate the module
config is set to those nodes. Feel free to delete or change them as you is 
needed.

The Privacy Policy and Legal Disclaimer configuration does nothing except
expose two new tokens. These tokens return the URL for those nodes, aliases
and translated. They can (and are) used for "I have read and agreed to X" 
checkboxes, so the URL is always up to date. See Office feature's form to see
it in action.

The tokens:

- [site:privacy-policy-url]
- [site:legal-disclaimer-url]
