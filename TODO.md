> aharabara/habarnam
## TUI Framework
#### V0.2.0
   - [x] Section
   - [x] List
   - [x] Input
   - [x] Divider
   - [x] Buttons
   - [x] TextArea
      - [x] Cursor displaing
      - [x] Editing in the middle

#### v0.2.1
   - [x] Enable extended ascii symbols

#### v0.3.0
   - [x] UI rendering via XML
   - [x] ComponentsContainerInterface
   
#### v0.3.1
   - [x] XML schema generation for UI, with attributes and events checks.

#### v0.3.2
   - [x] Correct surface partition for components      
   - [x] View switching
   

#### v0.4.0
   - [x] New line correct handling for textarea

#### v0.4.5
   - [x] CSS files for components instead of xml attributes

#### v0.5.0
   - [ ] XML tree update for dynamical elements (append on event if no mapping was specified)
   - [ ] Iframe to load another view. (need to think)
   - [x] Script to create project tree for easy start
   - [ ] Refactor Application and app.php
   - [ ] Move some logic from ViewRender to Builders
      - [ ] ComponentBuilder
      - [ ] SurfaceBuilder
         - [ ] MarginBox
         - [x] PaddingBox
      - [ ] Move focus from index to selector
      - [ ] Encapsulate child components into container (do not expose them anymore)

#### V0.6.0
 - [X] Container
 - [x] Eloquent and SQLite
 - [ ] Loading state (overlay?) for component
 - [ ] Queue process for delayed tasks
   
#### v0.7.0
 - [ ] Notifications
 - [ ] TemporaryComponentInterface
 - [ ] SelectBox
 - [ ] CheckBox

#### v0.8.0
   - [ ] ScrollableComponentInterface
   - [ ] Scrolling in:
       - [ ] Textarea
       - [ ] SelectBox
       - [ ] CheckBox
       - [ ] Section


#### v0.9.0
  - [ ] Keyboard shortcuts
  - [x] `Cursor extends Position` instead of `cursorPos`

#### v1.0.0
   - [X] PSR-4 namespacing
   - [ ] Documentation
   - [ ] Final classes and accessible methods
   - [ ] 3 or more applications without bugs
   - [ ] composer create-project
   - [ ] 5 articles on different resources


#### ToDo
   - [ ] List
     - [ ] Reordering
   - [ ] Table
     - [ ] Reordering 
     - [ ] Columns and rows
     - [ ] Editable
   - [ ] Progress bar
   - [ ] Modal
   - [ ] Tabs
   - [ ] CheckBox
   - [ ] SelectBox
      - [ ] Multiple
   - [x] Buttons
   - [ ] TextArea
      - [ ] Read only
      - [ ] Syntax highlighting
   - [ ] Menu bar

 - Features
   - [ ] Autocomplete for input and textareas
   - [ ] Spellcheck for input and textareas
   - [ ] Copy&Paste
