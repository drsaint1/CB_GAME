import React, { useState, useEffect, useRef, useCallback } from "react";
import Sidebar from "../sidebar";
import Header from "../header";
import LogoutModal from "../utilities/modals/logout";
import Cookies from "js-cookie";
import { useNavigate } from "@remix-run/react";
import ChatModal from "../livechats/chatbox";
import { toast } from 'sonner';
// import ChatInbox from "../../routes/conversations.$botId";

export default function WebSocketLayout() {
  const navigate = useNavigate();



  const [messages, setMessages] = useState([]);
  const [convo, setConvo] = useState([]);
  const [dynamicUserid, setdynamicUserid] = useState('');
  const [dynamicCustomerName, setdynamicCustomerName] = useState('');
  const [showChats, setShowChats] = useState(false);
  const [changeLivechat, setChangeLivechat] = useState(false);
  const [typing, setTyping] = useState(false);
  const [inputMessage, setInputMessage] = useState('');
  // const chatboxRef = useRef(null);
  const messagesEndRef = useRef(null);
  // const router = useNavigate();
  // const [offset, setOffset] = useState(0);
  // const limit = 10; // Number of messages to fetch at a time
  // const hasMoreMessages = useRef(true);
  // const isFetching = useRef(false); // New ref to prevent multiple fetches
  const [online, setOnlineStatus] = useState("");
  const [connectId, setConnectId] = useState("");
 
  const [receiverUserId, setRecieverUserId] = useState("");
  // const receiverUserId = 76; // Example recipient's user ID
  const socketRef = useRef(null);
  const audioRef = useRef(null);
  const [isConnected, setIsConnected] = useState(false);
  const [receiverUserOnline, setReceiverUserOnline] = useState(false);
  let typingTimeout;
  const notificationSoundRef = useRef(null);

  const [isModalVisible, setIsModalVisible] = useState(false); // State to control modal visibility
  const [currentRequest, setCurrentRequest] = useState(null); // Store the current connection request

  //FOR SHOWING CHAT
  const [isModalOpen, setIsModalOpen] = useState(false);

  const uid = Cookies.get('uid');
  const [fromUserId, setFromUserId] = useState(uid);


  const openModal = () => setIsOpen(true);
  const closeModal = () => setIsOpen(false);

  const toggleStatus = () => {
    setStatus(status === "standby" ? "online" : "standby");
  };

  // Close modal
  const handleCloseModal = () => {
    setIsModalOpen(false);
  };

  const handleOpenModal = () => {
    setIsModalOpen(true);
  }

  const connectWebSocket = () => {
    // toast.loading("loading");
    console.log('helllo');

    socketRef.current = new WebSocket(`ws://localhost:8080?user_id=${fromUserId}`);
    // socketRef.current = new WebSocket(`ws://localhost:8080?user_id=${fromUserId}?bot=${botid}`);

    socketRef.current.onopen = () => {
      const userid = Cookies.get('uid');
      console.log('WebSocket connection established');
      setIsConnected(true);
      setFromUserId(userid);
      sendMyStatus(fromUserId);
      toast.success("LiveMode Standby Active");
      toast.dismiss();
      localStorage.setItem("isConnected", true);
      localStorage.setItem("online", true);
      setOnlineStatus(true);
    };

    socketRef.current.onmessage = (event) => {
      try {
        const data = JSON.parse(event.data);
        handleMessage(data); // Function to handle incoming messages
        if (data.type === 'history') {
          setMessages(data.data); // Set entire conversation histo
        }
        if (data.type === 'convo') {
          setConvo(data.conversation); // Set entire conversation history
        }
        if (data.from_user && data.message) {
          setMessages((prevMessages) => [...prevMessages, data]);
          messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
          alert(messages);
          if (data.from_user !== fromUserId && notificationSoundRef.current) {
            notificationSoundRef.current.play();
          }
          //there is a problem here any user can send message to the live agent without him accepting before hand, 
          //another issue is if this is not set, the 
          //what i need to fix the button to send from the user side must be disabled until the live agent accepts the connection request
          // setRecieverUserId(data.from_user);
        }

        if (data.status_type) {
          setOnlineStatus(data.status_type);
        }
        if (data.user_id_status) {
          setConnectId(data.user_id_status);
        }

        if (data.connection_request) {
          setCurrentRequest(data); // Store request data
          console.log('data', data);
          setIsModalVisible(true); // Show modal
        }

        if (data.typing && data.from_user !== fromUserId) {
          setTyping(true);
          clearTimeout(typingTimeout);
          typingTimeout = setTimeout(() => {
            setTyping(false);
          }, 1000);
        }

        if (data.error) {
          alert(data.error);
        }

        console.log("Received message:", data);
      } catch (error) {
        console.error('Error parsing WebSocket message:', error);
      }
    };

    socketRef.current.onclose = () => {
      console.log('Livemode connection closed');
      toast.error("Livemode connection closed");
      setIsConnected(false);
      setOnlineStatus(false);
      toast.dismiss();
      localStorage.setItem("isConnected", false);
      localStorage.setItem("online", false);
    };

    socketRef.current.onerror = (error) => {
      console.error('Livemode error:', error);
      toast.error('Livemode error:', error);
      toast.dismiss();
      setIsConnected(false);
      setOnlineStatus(false);
      // sendDisconnectionMessage(receiverUserId);
      // sendDisconnectionMessageToAll();
    };

    return () => {
      socketRef.current.close();
    };

  }

  const handleTyping = () => {
    if (socketRef.current && !typing) {
      socketRef.current.send(JSON.stringify({ typing: true, from_user: fromUserId, to_user: receiverUserId }));
      setTyping(true);
      clearTimeout(typingTimeout);
      typingTimeout = setTimeout(() => {
        setTyping(false);
      }, 2000);
    }
  };


  const sendMyStatus = (fromUserId) => {
    // Ensure fromUserId is a string or number
    if (typeof fromUserId === "string" || typeof fromUserId === "number") {
      const AgentStatusCheck = {
        from_user: fromUserId,
        agent_online_status_check: true
      };
      console.log('Sending Agent Status message:', AgentStatusCheck);

      if (socketRef.current && socketRef.current.readyState === WebSocket.OPEN) {
        socketRef.current.send(JSON.stringify(AgentStatusCheck));
      }
    } else {
      console.error("Error: fromUserId is not a valid primitive type:", fromUserId);
    }
  };

  const sendDisconnectMyStatus = (fromUserId) => {
    // Ensure fromUserId is a string or number
    if (typeof fromUserId === "string" || typeof fromUserId === "number") {
      const AgentStatusCheck = {
        from_user: fromUserId,
        agent_online_status_check: false
      };
      console.log('Sending Agent Status message:', AgentStatusCheck);

      if (socketRef.current && socketRef.current.readyState === WebSocket.OPEN) {
        socketRef.current.send(JSON.stringify(AgentStatusCheck));
      }
    } else {
      console.error("Error: fromUserId is not a valid primitive type:", fromUserId);
    }
  };

  const disconnectWebSocket = useCallback(() => {
    if (socketRef.current) {
      sendDisconnectMyStatus(fromUserId); // Ensure `fromUserId` is defined and not an event
      socketRef.current.close();
      socketRef.current = null;
      setIsConnected(false);
      setOnlineStatus(false);

      localStorage.setItem("isConnected", false);
      localStorage.setItem("online", false);
      localStorage.setItem("fromUserId", "");
    }
  }, [fromUserId]);



  // const sendDisconnectionMessage = (toUser) => {
  //   // if (socketRef.current && socketRef.current.readyState === WebSocket.OPEN) {
  //   const disconnectionMessageData = {
  //     from_user: fromUserId,
  //     to_user: toUser,
  //     disconnected: true
  //   };
  //   console.log('Sending disconnection message:', disconnectionMessageData);
  //   socketRef.current.send(JSON.stringify(disconnectionMessageData));
  //   // }
  // };

  // const sendDisconnectionMessageToAll = () => {
  //   // if (socketRef.current && socketRef.current.readyState === WebSocket.OPEN) {
  //   const disconnectionMessageData = {
  //     from_user: fromUserId,
  //     // to_user: toUser,
  //     disconnected: true
  //   };
  //   console.log('Sending disconnection message:', disconnectionMessageData);
  //   socketRef.current.send(JSON.stringify(disconnectionMessageData));
  //   // socketRef.current.send(JSON.stringify(connectionAcceptanceData));
  //   // }
  // };








  // Function to handle incoming messages
  const handleMessage = useCallback((data) => {
    if (data.connection_request) {
      console.log(data);
      setCurrentRequest(data); // Store request data
      setIsModalVisible(true); // Show modal
    }

    if(data.stop_connection_request){
      setIsModalVisible(false);
      setShowChats(false);
    }
  }, []);



  // const sendMessage = () => {
  //   if (socketRef.current && inputMessage.trim()) {
  //     const messageData = {
  //       from_user: fromUserId,
  //       to_user: receiverUserId,
  //       message: inputMessage,
  //     };

  //     socketRef.current.send(JSON.stringify(messageData));
  //     setMessages([...messages, messageData]); // Update UI with new message
  //     setInputMessage(''); // Clear input field
  //   }
  // };

  // Handle send message
  const handleSendMessage = (message) => {
    // const newMessage = { from_user: true, message, seen: false };
    // setMessages([...messages, newMessage]);
    // if (socketRef.current && inputMessage.trim()) {
    if (socketRef.current) {
      const messageData = {
        from_user: fromUserId,
        to_user: receiverUserId,
        chatbotId: current.chatbotId,
        message: message,
        seen: false,
      };

      socketRef.current.send(JSON.stringify(messageData));
      setMessages([...messages, messageData]); // Update UI with new message
      setInputMessage(''); // Clear input field
    }
  };

  // Handle file send
  const handleSendFile = (file) => {
    console.log("File uploaded:", file);
  };



  // const checkReceiverStatus = (receiverId) => {
  //     if (receiverId && socketRef.current) {
  //         const statusCheckData = {
  //             from_user: fromUserId,
  //             to_user: receiverId,
  //             check_status: true
  //         };
  //         socketRef.current.send(JSON.stringify(statusCheckData));
  //     }
  // };



  // const sendConnectionRequest = () => {

  //     if (!receiverUserOnline) {
  //         toast.error("The user you are trying to connect with is offline.");
  //         return;
  //     }

  //     if (!receiverUserOnline) {
  //         toast.error(fromUserId);
  //         return;
  //     }

  //     const connectionRequestData = {
  //         from_user: fromUserId,
  //         to_user: receiverUserId,
  //         connection_request: true
  //     };
  //     console.log('Sending connection request:', connectionRequestData);
  //     socketRef.current.send(JSON.stringify(connectionRequestData));
  // };

  // Function to handle accept action
  const handleAccept = () => {
    if (currentRequest) {
      //currentRequest.uniqueid
      sendConnectionAcceptance(currentRequest.from_user,currentRequest.requestId);
      setRecieverUserId(currentRequest.from_user);
      setChangeLivechat(true);
      setdynamicCustomerName(currentRequest.customer_name);
      setdynamicUserid(currentRequest.from_user);
      setShowChats(true);
      setIsModalVisible(false); // Close modal

      setIsModalOpen(true);

      // closeModal();
    }
  };

  // Function to handle reject action
  const handleReject = () => {
    if (currentRequest) {
      sendRejection(currentRequest.from_user,currentRequest.requestId);
      setIsModalVisible(false); // Close modal
      // closeModal();
    }
  };

  const sendConnectionAcceptance = (toUser,requestId) => {
    const connectionAcceptanceData = {
      from_user: fromUserId,
      to_user: toUser,
      requestId:requestId,
      connection_accepted: true
    };

    console.log('requestid',requestId);
    // alert(requestId);
    // if handle new chat ,clear previous chat, set the userid chatting with
    console.log('Sending connection acceptance:', connectionAcceptanceData);
    socketRef.current.send(JSON.stringify(connectionAcceptanceData));
  };

  const sendRejection = (toUser,requestId) => {
    const connectionAcceptanceData = {
      from_user: fromUserId,
      to_user: toUser,
      requestId: requestId,
      rejected: true
    };
    console.log('requestid',requestId);
    // alert(requestId);
    console.log('Sending connection acceptance:', connectionAcceptanceData);
    socketRef.current.send(JSON.stringify(connectionAcceptanceData));
  };


  useEffect(() => {
    setdynamicUserid(dynamicUserid);
    // setShowChats(true);
  }, [changeLivechat]);




  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [messages]);




  const connectSync = (convouserid) => {
    // alert(convouserid);
    setdynamicUserid(convouserid);
    setShowChats(true);

  }

  // Load user ID from local storage on mount
  useEffect(() => {
    const storedUserId = localStorage.getItem("fromUserId");
    const storedIsConnected = localStorage.getItem("isConnected") === "true";
    const storedOnline = localStorage.getItem("online") === "true";

    if (storedUserId) {
      setFromUserId(storedUserId);
    }

    if (storedIsConnected) {
      console.log("reconnected after cut off");
      connectWebSocket();
      // setIsConnected()
    } else {
      setIsConnected(false);
    }

    setOnlineStatus(storedOnline);
  }, []);






  return (
    <div className="flex">
      {/* {children} */}
      {/* {isConnected === true && ( */}
      <div className="fixed top-25 text-xs  bg-green-500 text-white py-2 px-4 flex items-center z-50 justify-around left-[40%] mx-auto rounded-lg md:w-5/12 animate-pulse  animate-sparkle">
        <p className="">{isConnected ? "Livemode Activated, You are now online and ready to receive customer chats" : "you are not online , your widget is currently offline, you won't receive messages from your widgets" }</p>
        <button className={`p-2 text-white rounded-md font-bold bg-[#cecece]`} onClick={handleOpenModal}>modal</button>
        {isConnected ? (
          <button className={`p-2 text-white rounded-md font-bold bg-red`} onClick={disconnectWebSocket} > Disconnect </button>
        ) : (
          <button className={`p-2 text-white rounded-md font-bold bg-[#cecece]`} onClick={connectWebSocket}>Connect</button>

        )}
      </div>
      {/* )} */}

      {currentRequest && isModalVisible && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-99">
          <div className="bg-white w-11/12 md:w-1/3 p-8 rounded-xl shadow-xl relative animate-fadeInUp">

            {/* Close button */}
            <button
              className="absolute top-4 right-4 text-gray-500 hover:text-gray-700 transition-colors"
              onClick={handleReject}
            >
              &times;
            </button>

            {/* Modal Title */}
            <h2 className="text-2xl font-bold text-center text-gray-800 mb-4">
              Incoming Connection Request
            </h2>

            {/* Modal Content */}
            <div className="space-y-4 text-center">
              <p className="text-xl text-gray-600">
                {`From User: `}
                <span className="font-semibold text-gray-900">{currentRequest.from_user}</span>
              </p>
              <p className="text-lg text-gray-600">
                {`Customer Name: `}
                <span className="font-semibold text-gray-900">{currentRequest.customer_name}</span>
              </p>

              <p className="text-lg text-gray-600">
                {`Request Title: `}
                <span className="font-semibold text-gray-900">{currentRequest.issueTitle}</span>
              </p>
              <p className="text-lg text-gray-600">
                {`Request Message: `}
                <span className="font-semibold text-gray-900">{currentRequest.issueMessage}</span>
              </p>
              <p className="text-lg text-gray-600">
                {`Browser: `}
                <span className="font-semibold text-gray-900">{currentRequest.browser}</span>
              </p>
              <p className="text-lg text-gray-600">
                {`Visitor Address: `}
                <span className="font-semibold text-gray-900">{currentRequest.ipaddress}</span>
              </p>
            </div>

            {/* Buttons */}
            <div className="flex justify-between space-x-4 mt-8">
              <button
                className="bg-red hover:bg-red text-white font-bold py-2 px-6 rounded-lg transition duration-300 ease-in-out"
                onClick={handleReject}
              >
                Reject
              </button>
              <button
                className="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-6 rounded-lg  transition duration-300 ease-in-out"
                onClick={handleAccept}
              //send the uniqueid connectioin reqeust with the function
              >
                Accept
              </button>
            </div>
          </div>
        </div>
      )}



      {showChats && (

        <ChatModal
          isOpen={isModalOpen}
          closeModal={handleCloseModal}
          dynamicCustomerName="John Doe"
          messages={messages}
          fromUserId={fromUserId}
          onSendMessage={handleSendMessage}
          onSendFile={handleSendFile}
        />
      )}


      {/* <ChatInbox
      messages={messages}
     /> */}

    </div>
  );
}
