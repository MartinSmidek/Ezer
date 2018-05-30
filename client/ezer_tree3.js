/*
Script: ezer_tree3.js
        - tree structure displaying

License: MIT-style license.

Inspired by:
        - mootree by Rasmus Schultz, <http://www.mindplay.dk>
	- WebFX xTree, <http://webfx.eae.net/dhtml/xtree/>
	- Destroydrop dTree, <http://www.destroydrop.com/javascripts/tree/>

Ported to ES6+jQuery by:
        - Martin Šmídek
*/
"use strict";
var MooTreeIcon= ['I','L','Lminus','Lplus','Rminus','Rplus','T','Tminus','Tplus','_closed','_doc','_open','minus','plus'];

// ====================================================================================> TreeControl
/*
  Class: MooTreeControl
  This class implements a tree control.

  Properties:
    root - returns the root <MooTreeNode> object.
    selected - returns the currently selected <MooTreeNode> object, or null if nothing is currently selected.

  Events:
    onExpand - called when a node is expanded or collapsed: function(node, state) - where node is the <MooTreeNode> object that fired the event, and state is a boolean meaning true:expanded or false:collapsed.
    onSelect - called when a node is selected or deselected: function(node, state) - where node is the <MooTreeNode> object that fired the event, and state is a boolean meaning true:selected or false:deselected.
    onClick - called when a node is clicked: function(node) - where node is the <MooTreeNode> object that fired the event.

  Parameters:
    The constructor takes two object parameters: config and options.
    The first, config, contains global settings for the tree control - you can use the configuration options listed below.
    The second, options, should contain options for the <MooTreeNode> constructor - please refer to the options listed in the <MooTreeNode> documentation.

  Config:
    div - a string representing the div Element inside which to build the tree control.
    mode - optional string, defaults to 'files' - specifies default icon behavior. In 'files' mode, empty nodes have a document icon - whereas, in 'folders' mode, all nodes are displayed as folders (a'la explorer).
    grid - boolean, defaults to false. If set to true, a grid is drawn to outline the structure of the tree.
    theme - string, optional, defaults to 'mootree.gif' - specifies the 'theme' GIF to use.
    loader - optional, an options object for the <MooTreeNode> constructor - defaults to {icon:'mootree_loader.gif', text:'Loading...', color:'a0a0a0'}
    onExpand - optional function (see Events above)
    onSelect - optional function (see Events above)
*/

class MooTreeControl {
  // ----------------------------------------------------------------------- constructor
  constructor (config, options) {
    options.control= this;               // make sure our new MooTreeNode knows who it's owner control is
    options.div= config.div;             // tells the root node which div to insert itself into
    this.root= new MooTreeNode(options); // create the root node of this tree control
    this.index= {};                      // used by the get() method
    this.enabled= true;                  // enable visual updates of the control
    this.theme= config.theme || 'mootree.gif';
    this.path= config.path || '';        // MS - path of images
    this.selected= null;                 // set the currently selected node to nothing
    this.mode= config.mode;              // mode can be "folders" or "files", and affects the default icons
    this.grid= config.grid;              // grid can be turned on (true) or off (false)
    this.onExpand= config.onExpand || function(){}; // called when any node in the tree is expanded/collapsed
    this.onSelect= config.onSelect || function(){}; // called when any node in the tree is selected/deselected
    this.onClick=  config.onClick  || function(){}; // called when any node in the tree is clicked
    this.root.update(true);
  }
  // ----------------------------------------------------------------------- insert
  // Creates a new node under the root node of this tree.
  // parameters: options - an object containing the same options available to the <MooTreeNode> constructor.
  // result: A new <MooTreeNode> instance.
  insert (options) {
    options.control= this;
    return this.root.insert(options);
  }
  // ----------------------------------------------------------------------- select
  // Sets the currently selected node.
  // This is called by <MooTreeNode> when a node is selected (e.g. by clicking it's title with the mouse).
  // parameters: node - the <MooTreeNode> object to select.
  select (node,left) {
    this.onClick(node,left); node.onClick(); // fire click events
    if (this.selected=== node) return; // already selected
    if (this.selected) {
      // deselect previously selected node:
      this.selected.select(false);
      this.onSelect(this.selected, false);
    }
    // select new node:
    this.selected= node;
    node.select(true);
    this.onSelect(node, true);
  }
  // ----------------------------------------------------------------------- expand
  // Expands the entire tree, recursively.
  expand () {
    this.root.toggle(true, true);
  }
  // ----------------------------------------------------------------------- collapse
  // Collapses the entire tree, recursively.
  collapse () {
    this.root.toggle(true, false);
  }
  // ----------------------------------------------------------------------- get
  // Retrieves the node with the given id - or null, if no node with the given id exists.
  // Parameters: id - a string, the id of the node you wish to retrieve.
  // Note: Node id can be assigned via the <MooTreeNode> constructor,
  //       e.g. using the <MooTreeNode.insert> method.
  get (id) {
    return this.index[id] || null;
  }
  // ----------------------------------------------------------------------- disable
  // Call this to temporarily disable visual updates -- if you need to insert/remove many nodes
  // at a time, many visual updates would normally occur. By temporarily disabling the control,
  // these visual updates will be skipped.
  // When you're done making changes, call <MooTreeControl.enable> to turn on visual updates
  // again, and automatically repaint all nodes that were changed.
  disable () {
    this.enabled= false;
  }
  // ----------------------------------------------------------------------- enable
  // Enables visual updates again after a call to <MooTreeControl.disable>
  enable () {
    this.enabled= true;
    this.root.update(true, true);
  }
}

// =======================================================================================> TreeNode
/*
  Class: MooTreeNode
    This class implements the functionality of a single node in a <MooTreeControl>.

  Note:
    You should not manually create objects of this class -- rather, you should use
    <MooTreeControl.insert> to create nodes in the root of the tree, and then use
    the similar function <MooTreeNode.insert> to create subnodes.

    Both insert methods have a similar syntax, and both return the newly created
    <MooTreeNode> object.

  Parameters:
    options - an object. See options below.

  Options:
    text - this is the displayed text of the node, and as such as is the only required parameter.
    id - string, optional - if specified, must be a unique node identifier. Nodes with id can be retrieved using the <MooTreeControl.get> method.
    color - string, optional - if specified, must be a six-digit hexadecimal RGB color code.
    open - boolean value, defaults to false. Use true if you want the node open from the start.
    icon - use this to customize the icon of the node. The following predefined values may be used: '_open', '_closed' and '_doc'. Alternatively, specify the URL of a GIF or PNG image to use - this should be exactly 18x18 pixels in size. If you have a strip of images, you can specify an image number (e.g. 'my_icons.gif#4' for icon number 4).
    openicon - use this to customize the icon of the node when it's open.
    data - an object containing whatever data you wish to associate with this node (such as an url and/or an id, etc.)

  Events:
    onExpand - called when the node is expanded or collapsed: function(state) - where state is a boolean meaning true:expanded or false:collapsed.
    onSelect - called when the node is selected or deselected: function(state) - where state is a boolean meaning true:selected or false:deselected.
    onClick - called when the node is clicked (no arguments).
*/

class MooTreeNode {
  // ----------------------------------------------------------------------- constructor
  constructor (options) {
    this.text=  options.text;           // the text displayed by this node
    this.title= options.title;          // the text displayed by this node +gn150203
    this.id=    options.id || null;     // the node's unique id
    this.nodes= [];                     // subnodes nested beneath this node (MooTreeNode objects)
    this.parent= null;                  // this node's parent node (another MooTreeNode object)
    this.last=  true;                   // a flag telling whether this node is the last (bottom) node of it's parent
    this.control= options.control;      // owner control of this node's tree
    this.selected= false;               // a flag telling whether this node is the currently selected node in it's tree
    this.color= options.color || null;  // text color of this node
    this.data=  options.data || {};     // optional object containing whatever data you wish to associate with the node (typically an url or an id)
    this.onExpand= options.onExpand || function(){}; // called when the individual node is expanded/collapsed
    this.onSelect= options.onSelect || function(){}; // called when the individual node is selected/deselected
    this.onClick=  options.onClick  || function(){}; // called when the individual node is clicked
    this.open=  options.open ? true : false; // flag: node open or closed?
    this.icon=  options.icon;
    this.openicon= options.openicon || this.icon;
    // add the node to the control's node index:
    if (this.id) this.control.index[this.id]= this;
    // create the necessary divs:
    this.div= {
      main:   jQuery(`<div class="mooTree_node">`),
      indent: jQuery(`<div>`),
      gadget: jQuery(`<div>`),
      icon:   jQuery(`<div>`),
      text:   jQuery(`<div class="mooTree_text">`),
      sub:    jQuery(`<div>`)
    };
    // put the other divs under the main div:
    this.div.main.append(this.div.indent);
    this.div.main.append(this.div.gadget);
    this.div.main.append(this.div.icon);
    this.div.main.append(this.div.text);
    // put the main and sub divs in the specified parent div:
    jQuery(options.div).append(this.div.main);
    jQuery(options.div).append(this.div.sub);
    // attach event handler to gadget:
    this.div.gadget
      .click( () => { this.toggle(); });
    // attach event handler to icon/text:
    this.div.icon
      .click( () => { this.control.select(this); });
    this.div.text
      .click( () => { this.control.select(this); });
    // attach left mouse click to icon/text
    this.div.icon
      .contextmenu( () => { this.control.select(this,true); return true; });
    this.div.text
      .contextmenu( () => { this.control.select(this,true); return true; });
  }
  // ----------------------------------------------------------------------- insert
  // Creates a new node, nested inside this one.
  // parameters: options - an object containing the same options available to the <MooTreeNode> constructor.
  // Returns: A new <MooTreeNode> instance.
  insert (options) {
    // set the parent div and create the node:
    options.div= this.div.sub;
    options.control= this.control;
    var node= new MooTreeNode(options);
    // set the new node's parent:
    node.parent= this;
    // mark this node's last node as no longer being the last, then add the new last node:
    var n= this.nodes;
    if (n.length) n[n.length-1].last= false;
    n.push(node);
    // repaint the new node:
    node.update();
    // repaint the new node's parent (this node):
    if (n.length == 1) this.update();
    // recursively repaint the new node's previous sibling node:
    if (n.length > 1) n[n.length-2].update(true);
    return node;
  }
  // ----------------------------------------------------------------------- remove
  // Removes this node, and all of it's child nodes. If you want to remove
  // all the childnodes without removing the node itself, use <MooTreeNode.clear>
  remove () {
    var p= this.parent;
    this._remove();
    p.update(true);
  }
  _remove () {
    // recursively remove this node's subnodes:
    var n= this.nodes;
    while (n.length) n[n.length-1]._remove();
    // remove the node id from the control's index:
    delete this.control.index[this.id];
    // remove this node's divs:
    this.div.main.empty();
    this.div.sub.empty();
    if (this.parent) {
      // remove this node from the parent's collection of nodes:
      var p= this.parent.nodes;
      for (var i= p.length; i--;) {
        if (p[i] === this) p.splice(i, 1);
      }
      // in case we removed the parent's last node, flag it's current last node as being the last:
      if (p.length) p[p.length-1].last= true;
    }
}
  // ----------------------------------------------------------------------- clear
  // Removes all child nodes under this node, without removing the node itself.
  // To remove all nodes including this one, use <MooTreeNode.remove>
  clear () {
    this.control.disable();
    while (this.nodes.length) this.nodes[this.nodes.length-1].remove();
    this.control.enable();
  }
  // ----------------------------------------------------------------------- update
  // Update the tree node's visual appearance.
  // Parameters: recursive - boolean, defaults to false. If true, recursively updates all nodes beneath this one.
  // invalidated - boolean, defaults to false. If true, updates only nodes that have been invalidated while the control has been disabled.
  update (recursive, invalidated) {
    var draw= true;
    if (!this.control.enabled) {
      // control is currently disabled, so we don't do any visual updates
      this.invalidated= true;
      draw= false;
    }
    if (invalidated) {
      if (!this.invalidated) {
        draw= false; // this one is still valid, don't draw
      }
      else {
        this.invalidated= false; // we're drawing this item now
      }
    }
    if (draw) {
      var x;
      // make selected, or not:
      if ( this.selected )
        this.div.main.addClass('mooTree_selected');
      else
        this.div.main.removeClass('mooTree_selected');
      // update indentations:
      var p= this, i= '';
      while (p.parent) {
        p= p.parent;
        i= this.getImg(p.last || !this.control.grid ? '' : 'I') + i;
      }
      this.div.indent.html(i);
      // update the text:
      x= this.div.text;
      x.empty();
      x.html(this.text);                            //+gn150203 show node text as html
      if ( this.title ) x.prop('title',this.title); //+gn150203 show node title
      if (this.color) x.css('color',this.color);
      // update the icon:
      this.div.icon.html(this.getImg( this.nodes.length
        ? ( this.open
          ? (this.openicon || this.icon || '_open')
          : (this.icon || '_closed') )
        : ( this.icon || (this.control.mode == 'folders' ? '_closed' : '_doc') ) ));
      // update the plus/minus gadget:
      this.div.gadget.html(this.getImg( ( this.control.grid
        ? ( this.control.root == this
          ? (this.nodes.length ? 'R' : '')
          : (this.last?'L':'T') )
        : ''
        ) + (this.nodes.length ? (this.open?'minus':'plus') : '') ));
       // show/hide subnodes:
      this.div.sub.css('display',this.open ? 'block' : 'none');
    }
    // if recursively updating, update all child nodes:
    if (recursive) {
      for (var k= 0; k<this.nodes.length; k++) {
        this.nodes[k].update(true, invalidated);
      }
    }
  }

  // ----------------------------------------------------------------------- getImg
  // Creates a new image, in the form of HTML for a DIV element with appropriate style.
  // You should not need to manually call this method. (though if for some reason you want to, you can)
  // parameters: name - the name of new image to create, defined by <MooTreeIcon> or located in an external file.
  // Returns: The HTML for a new div Element.
  getImg (name) {
    var html= '<div class="mooTree_img"';
    if (name != '') {
      var img= this.control.theme;
      var i= MooTreeIcon.indexOf(name);
      if (i == -1) {
        // custom (external) icon:
        var x= name.split('#');
        img= x[0];
        i= (x.length == 2 ? parseInt(x[1])-1 : 0);
      }
      html+= ` style="background-image:url(${this.control.path + img}); background-position:-${(i*18)}px 0px"`; // MS
    }
    html+= "></div>";
    return html;
  }
  // ----------------------------------------------------------------------- toggle
  // By default (with no arguments) this function toggles the node between expanded/collapsed.
  // Can also be used to recursively expand/collapse all or part of the tree.
  // parameters:
  //   recursive - boolean, defaults to false. With recursive set to true, all child nodes are recursively toggle to this node's new state.
  //   state - boolean. If undefined, the node's state is toggled. If true or false, the node can be explicitly opened or closed.
  //   depth - depth of recursion, if undefined then recursion is unlimited
  toggle (recursive, state, depth) {
    depth= depth === undefined ? 999 : depth;
    this.open= (state === undefined ? !this.open : state);
    this.update();
    this.onExpand(this.open);
    this.control.onExpand(this, this.open);
    if (recursive && depth > 0 ) this.nodes.forEach( function(node) {
      node.toggle(true, this.open, depth-1);
    }, this);
  }
  // ----------------------------------------------------------------------- select
  // Called by <MooTreeControl> when the selection changes.
  // You should not manually call this method - to set the selection, use the <MooTreeControl.select> method.
  select (state) {
    this.selected= state;
    this.update();
    this.onSelect(state);
  }
}
