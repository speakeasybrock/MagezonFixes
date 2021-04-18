# Speakeasyco Magezon Fixes

Ititially just an extension of Magezon's core product grid to allow sorting by category position.

## Functionality

This module introduces two new data sources to the Magezon product grid options ("Category" and "Category Featured"). Our extension looks for these new sources and, if selected, loads the product collection by category and filters by category position. If other sorting options are chosen (e.g. "price: low to high"), those options override the default position sorting. 

"Category Featured" is the same as selecting "Featured", but just using the category data source instead (so position-based sorting can be used).